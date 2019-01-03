<?php

namespace Glued\Controllers\Wiki;
use Glued\Controllers\Controller;

class WikiController extends Controller
{
    
    // shows basic page with all wikis
    public function wikisGui($request, $response)
    {
        $wiki_output = '';
        $this->container->db->orderBy("c_uid","asc");
        $items = $this->container->db->get('t_wiki');
        if (count($items) > 0) {
            foreach ($items as $data) {
                // pocet articles
                $this->container->db->where("c_wiki_uid", $data['c_uid']);
                $pocet = $this->container->db->getValue("t_wiki_articles", "count(*)");
                $wiki_output .= '
                    <tr>
                        <th scope="row">'.$data['c_uid'].'</th>
                        <td>'.$data['c_name'].'</td>
                        <td>'.$data['c_url'].'</td>
                        <td>'.$pocet.'</td>
                        <td><a href="'.$this->container->router->pathFor('wiki.page.main', ['wikiurl' => $data['c_url']]).'">frontend</a></td>
                    </tr>
                ';
                // <a href="'.$this->container->router->pathFor('consumables.editform', ['id' => $data['c_uid']]).'">edit</a>
            }
        }
        else {
            $wiki_output = '';
        }
        
        return $this->container->view->render($response, 'wiki/admin.twig', array('wiki_output' => $wiki_output));
    }
    
    // show form for add new wiki
    public function addWikiForm($request, $response)
    {
        
        return $this->container->view->render($response, 'wiki/addwiki.twig', array(        ));
    }
    
    // zpracuj form na new wiki
    public function addWikiAction($request, $response)
    {
        $name = $request->getParam('name');
        $url = $request->getParam('url');
        
        // vlozime do wiki
        $data = Array ("c_name" => $name, "c_url" => $url);
        $wiki_uid = $this->container->db->insert('t_wiki', $data);
        
        // kontrola ze se vlozilo
        if ($wiki_uid > 0) {
            // vlozime uvodni article
            $data = Array ("c_wiki_uid" => $wiki_uid, "c_title" => "main", "c_url" => "", "c_creator" => $_SESSION['user_id']);
            $insert = $this->container->db->insert('t_wiki_articles', $data);
            
            // TODO kontrola ze se vlozilo
            
            $this->container->flash->addMessage('info', 'new wiki was added with name '.$name);
        }
        else {
            $this->container->flash->addMessage('error', 'new wiki was not added.'.$this->container->db->getLastError());
        }
        
        return $response->withRedirect($this->container->router->pathFor('wiki.admin'));
    }
    
    // zpracuj form na new article
    public function addArticleAction($request, $response)
    {
        $wiki_uid = $request->getParam('wiki_uid');
        $title = $request->getParam('title');
        $url = $request->getParam('url');
        $wiki_url = $request->getParam('wiki_url');
        
        // TODO kontrola ze se url neshoduje s existujici pro tuto wiki
        
        // vlozime do article
        $data = Array ("c_wiki_uid" => $wiki_uid, "c_title" => $title, "c_url" => $url, "c_creator" => $_SESSION['user_id']);
        $insert = $this->container->db->insert('t_wiki_articles', $data);
        
        // TODO kontrola ze se vlozilo
        
        $this->container->flash->addMessage('info', 'new wiki article was added with title '.$title);
        
        return $response->withRedirect($this->container->router->pathFor('wiki.page.article', ['wikiurl' => $wiki_url, 'articleurl' => $url]));
    }
    
    // zobrazeni main page wiki
    public function mainPage($request, $response, $args)
    {
        $teoreticka_wikiurl = $args['wikiurl'];
        
        $chybovy_vystup = '';
        
        // zjistime jestli existuje
        $this->container->db->where("c_url", $teoreticka_wikiurl);
        $data = $this->container->db->getOne('t_wiki');
        
        if (isset($data['c_uid'])) {
            // urcime prava
            $right_add = false;
            $right_edit = false;
            
            // TODO, poradne
            
            if ($this->container->auth->check()) {
                $right_add = true;
                $right_edit = true;
            }
            
            
            // nacteme articles, krome main
            $this->container->db->where("c_wiki_uid", $data['c_uid']);
            $this->container->db->where("c_url", "", "!=");
            $articles_data = $this->container->db->get('t_wiki_articles');
            
            if (count($articles_data) > 0) {
                $subarticles = array();
                foreach($articles_data as $one_article_data) {
                    $subarticles[] = '<a href="'.$this->container->router->pathFor('wiki.page.article', ['wikiurl' => $data['c_url'], 'articleurl' => $one_article_data['c_url']]).'">'.$one_article_data['c_title'].'</a>';
                }
                $subarticles_output = implode(' | ', $subarticles);
            }
            else {
                $subarticles_output = 'no other articles yet';
            }
            
            // nacteme main article
            $this->container->db->where("c_wiki_uid", $data['c_uid']);
            $this->container->db->where("c_url", "");
            $main_article_data = $this->container->db->getOne('t_wiki_articles');
            
            // pridame javascript pro editaci
            $additional_javascript = '';
            //                             name: "editovany_text", // with an optional filename
            if ($right_edit) {
                $additional_javascript = '
                <script type="text/javascript">
                    function proved_editaci_clanku(zdroj_mark, cil_html) {
                        var zdroj = $("#" + zdroj_mark).text();
                        var vysledne_html;
                        
                        const sePointer = new Stackedit({
                          url: \'https://stackedit.industra.space/app\'
                        });
                        
                        // Open the iframe
                        sePointer.openFile({
                            content: {
                                text: zdroj
                            }
                        });
                        
                        // Listen to StackEdit events and apply the changes to the textarea.
                        // teoreticky to nemusime delat a staci az pri zavreni
                        sePointer.on("fileChange", (file) => {
                            $("#" + zdroj_mark).text(file.content.text);
                            $("#" + cil_html).html(file.content.html);
                        });
                        
                        // listen to close
                        sePointer.on("close", (file) => {
                            wiki_save_article('.$main_article_data['c_uid'].', "https://'.$this->container['settings']['glued']['hostname'].$this->container->router->pathFor('wiki.article.change').'", zdroj_mark, cil_html);
                        });
                        
                    }
                </script>
                ';
            }
            
            return $this->container->view->render($response, 'wiki/page.main.twig', array(
                'wiki_data' => $data,
                'right_add' => $right_add,
                'right_edit' => $right_edit,
                'subarticles_output' => $subarticles_output,
                'main_article' => $main_article_data,
                'additional_javascript' => $additional_javascript));
        }
        else {
            $chybovy_vystup .= 'Wiki '.$args['wikiurl'].' does not exists';
            return $this->container->view->render($response, 'wiki/page.error.twig', array('chybovy_vystup' => $chybovy_vystup));
        }
    }
    
    // zobrazeni article ve wiki
    public function articlePage($request, $response, $args)
    {
        $teoreticka_wikiurl = $args['wikiurl'];
        $teoreticka_article = $args['articleurl'];
        
        $chybovy_vystup = '';
        
        // zjistime jestli wiki existuje
        $this->container->db->where("c_url", $teoreticka_wikiurl);
        $data = $this->container->db->getOne('t_wiki');
        
        if (isset($data['c_uid'])) {
            // urcime prava
            $right_add = false;
            $right_edit = false;
            
            // TODO, poradne
            
            if ($this->container->auth->check()) {
                $right_add = true;
                $right_edit = true;
            }
            
            // nacteme vybrane article, pokud existuje
            $this->container->db->where("c_wiki_uid", $data['c_uid']);
            $this->container->db->where("c_url", $teoreticka_article);
            $article_data = $this->container->db->getOne('t_wiki_articles');
            
            // pridame javascript pro editaci
            $additional_javascript = '';
            //                             name: "editovany_text", // with an optional filename
            if ($right_edit) {
                $additional_javascript = '
                <script type="text/javascript">
                    function proved_editaci_clanku(zdroj_mark, cil_html) {
                        var zdroj = $("#" + zdroj_mark).text();
                        var vysledne_html;
                        
                        const sePointer = new Stackedit({
                          url: \'https://stackedit.industra.space/app\'
                        });
                        
                        // Open the iframe
                        sePointer.openFile({
                            content: {
                                text: zdroj
                            }
                        });
                        
                        // Listen to StackEdit events and apply the changes to the textarea.
                        // teoreticky to nemusime delat a staci az pri zavreni
                        sePointer.on("fileChange", (file) => {
                            $("#" + zdroj_mark).text(file.content.text);
                            $("#" + cil_html).html(file.content.html);
                        });
                        
                        // listen to close
                        sePointer.on("close", (file) => {
                            wiki_save_article('.$article_data['c_uid'].', "https://'.$this->container['settings']['glued']['hostname'].$this->container->router->pathFor('wiki.article.change').'", zdroj_mark, cil_html);
                        });
                        
                    }
                </script>
                ';
            }
            
            if (isset($article_data['c_uid'])) {
                return $this->container->view->render($response, 'wiki/page.article.twig', array(
                    'wiki_data' => $data,
                    'right_add' => $right_add,
                    'right_edit' => $right_edit,
                    'article_data' => $article_data,
                    'additional_javascript' => $additional_javascript));
            }
            else {
                $chybovy_vystup .= 'Article '.$args['articleurl'].' does not exists';
                return $this->container->view->render($response, 'wiki/page.error.twig', array('chybovy_vystup' => $chybovy_vystup));
            }
        }
        else {
            $chybovy_vystup .= 'Wiki '.$args['wikiurl'].' does not exists';
            return $this->container->view->render($response, 'wiki/page.error.twig', array('chybovy_vystup' => $chybovy_vystup));
        }
    }
    
    // ulozeni clanku fungujici jako ajax api
    // TODO zatim to budeme prepisovat. varianty udelam pozdeji
    public function articleApiChange($request, $response)
    {
        $article_id = $request->getParam('article_id');
        $article_markup = $request->getParam('article_markup');
        $article_html = $request->getParam('article_html');
        
        if (!empty($article_markup)) {
            
            // nacteme si jestli to mame
            $this->container->db->where("c_uid", $article_id);
            $action = $this->container->db->getOne('t_wiki_articles');
            if ($this->container->db->count > 0) {
                
                // TODO overime ze mame pravo to menit
                
                
                // updatujeme (TODO pozdeji vlozime novou verzi)
                $data = Array ('c_text' => $article_markup, 'c_html' => $article_html);
                $this->container->db->where("c_uid", $article_id);
                $this->container->db->update('t_wiki_articles', $data);
            }
            
        }
        
        // vratime prosty text
        //$this->container->flash->addMessage('info', 'Action was renamed');
        $response->getBody()->write('ok');
        return $response;
    }
    
    
}
