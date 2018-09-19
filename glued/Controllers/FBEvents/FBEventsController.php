<?php

namespace Glued\Controllers\FBEvents;
use Glued\Controllers\Controller;

class FBEventsController extends Controller
{
    
    // shows pages
    public function fbeventsMain($request, $response)
    {
        // nacteme si stranky
        $pages_output = '';
        $pages = $this->container->db->get('t_facebook_pages');
        if ($this->container->db->count > 0) {
            foreach ($pages as $page) {
                
                $sloupce = array("c_event_uid");
                $this->container->db->where('c_page_id', $page['c_id']);
                $this->container->db->get('t_facebook_page_events', null, $sloupce);
                
                $pages_output .= '<tr>
                    <td>'.$page['c_id'].'</td>
                    <td>'.$page['c_fb_id'].'</td>
                    <td>'.$page['c_fb_name'].'</td>
                    <td>'.$this->container->db->count.'</td>
                    <td><a href="'.$this->container->router->pathFor('fbevents.page', ['id' => $page['c_id']]).'">show events</a> <a href="'.$this->container->router->pathFor('fbevents.editpage', ['id' => $page['c_id']]).'">edit page</a></td>
                </tr>';
            }
        }
        
        // nacteme si tokeny
        $tokens_output = '';
        $tokens = $this->container->db->get('t_facebook_tokens');
        if ($this->container->db->count > 0) {
            foreach ($tokens as $token) {
                
                $tokens_output .= '<tr>
                    <td>'.$token['c_id'].'</td>
                    <td>'.$token['c_title'].'</td>
                    <td><a href="'.$this->container->router->pathFor('fbevents.edittoken', ['id' => $token['c_id']]).'">edit token</a></td>
                </tr>';
            }
        }
        
        return $this->container->view->render($response, 'fbevents/gui.twig',
            array(
                'pages_output' => $pages_output,
                'tokens_output' => $tokens_output
            )
        );
    }
    
    // show events on one page
    public function fbeventsPage($request, $response, $args)
    {
        $page_id = $args['id'];
        $this->container->db->where('c_id', $page_id);
        $page = $this->container->db->getOne('t_facebook_pages');
        
        $events_output = '';
        
        $sloupce = array("e.c_uid", "e.c_event_id", "e.c_data->>'$.name' as name", "e.c_data->>'$.start_time' as start_time");
        $this->container->db->join("t_facebook_events e", "p.c_event_uid=e.c_uid", "LEFT");
        $this->container->db->where('p.c_page_id', $page_id);
        //$this->container->db->where('c_new', 1);
        $this->container->db->orderBy("start_time","desc");
        $events = $this->container->db->get('t_facebook_page_events p', null, $sloupce);
        if ($this->container->db->count > 0) {
            foreach ($events as $event) {
                
                $cas = strtotime($event['start_time']);
                
                // zjistime jestli je vlozeny jako vektor
                $this->container->db->where('c_source_table', 't_facebook_events');
                $this->container->db->where('c_source_object', $event['c_uid']);
                $vektor = $this->container->db->getOne('t_vectors');
                
                if (isset($vektor['c_uid']) and !empty($vektor['c_data'])) {
                    $vektor_vystup = '<i class="fa fa-external-link-square"></i>';
                }
                else {
                    $vektor_vystup = '';
                }
                
                $events_output .= '<tr>
                    <td>'.$event['c_uid'].'</td>
                    <td>'.$vektor_vystup.'</td>
                    <td>'.$event['c_event_id'].'</td>
                    <td><a href="'.$this->container->router->pathFor('fbevents.event', ['id' => $event['c_uid']]).'">'.$event['name'].'</a></td>
                    <td>'.date('j.n. Y H:i', $cas).'</td>
                    <td></td>
                </tr>';
            }
        }
        
        
        return $this->container->view->render($response, 'fbevents/page.twig',
            array(
                'page_db_id' => $page_id,
                'page_name' => $page['c_fb_name'],
                'page_id' => $page['c_fb_id'],
                'events_output' => $events_output
            )
        );
    }
    
    // show all events
    public function fbeventsAllEvents($request, $response)
    {
        $events_output = '';
        
        $sloupce = array("c_uid", "c_event_id", "c_data", "c_data->>'$.name' as name", "c_data->>'$.start_time' as start_time");
        $this->container->db->orderBy("start_time","desc");
        $events = $this->container->db->get('t_facebook_events', null, $sloupce);
        if ($this->container->db->count > 0) {
            foreach ($events as $event) {
                $event_json = json_decode($event['c_data'], true);
                $cas = strtotime($event['start_time']);
                // kdo provozuje (stranka)
                $provozovatel = '';
                $sloupce = array("e.c_id", "e.c_fb_name");
                $this->container->db->join("t_facebook_pages e", "p.c_page_id=e.c_id", "LEFT");
                $this->container->db->where('p.c_event_uid', $event['c_uid']);
                $pages = $this->container->db->get('t_facebook_page_events p', null, $sloupce);
                $pocet_poradatelu = $this->container->db->count;
                if ($pocet_poradatelu > 0) {
                    $page = $pages[0];
                    $provozovatel = '<a href="'.$this->container->router->pathFor('fbevents.page', ['id' => $page['c_id']]).'">'.$page['c_fb_name'].'</a>';
                    if ($pocet_poradatelu > 1) {
                        $provozovatel .= ' and '.($pocet_poradatelu - 1).' more';
                    }
                }
                else {
                    $provozovatel = '-';
                }
                
                // zjistime jestli je vlozeny jako vektor
                $this->container->db->where('c_source_table', 't_facebook_events');
                $this->container->db->where('c_source_object', $event['c_uid']);
                $vektor = $this->container->db->getOne('t_vectors');
                
                if (isset($vektor['c_uid']) and !empty($vektor['c_data'])) {
                    $vektor_vystup = '<i class="fa fa-external-link-square"></i>';
                }
                else {
                    $vektor_vystup = '';
                }
                
                $events_output .= '<tr>
                    <td>'.$event['c_uid'].'</td>
                    <td>'.$vektor_vystup.'</td>
                    <td><a href="'.$this->container->router->pathFor('fbevents.event', ['id' => $event['c_uid']]).'">'.$event['name'].'</a></td>
                    <td>'.$event_json['place']['location']['street'].', '.$event_json['place']['location']['city'].'</td>
                    <td>'.$provozovatel.'</td>
                    <td>'.date('j.n. Y H:i', $cas).'</td>
                    <td></td>
                </tr>';
            }
        }
        
        
        return $this->container->view->render($response, 'fbevents/allevents.twig',
            array(
                'events_output' => $events_output
            )
        );
    }
    
    
    // show info about one event
    public function fbeventsEvent($request, $response, $args)
    {
        $event_id = $args['id'];
        $this->container->db->where('c_uid', $event_id);
        $event = $this->container->db->getOne('t_facebook_events');
        
        $event['c_data'] = str_replace('\n', '<br>', $event['c_data']);
        
        $event_json = json_decode($event['c_data'], true);  // vytvori objekt, ktery pak muzeme ve view zobrazovat s konvenci event_json.name atd.
        $event_output = '';
        
        $fb_pages_pole = array();
        $sloupce = array("e.c_id", "e.c_fb_name");
        $this->container->db->join("t_facebook_pages e", "p.c_page_id=e.c_id", "LEFT");
        $this->container->db->where('p.c_event_uid', $event_id);
        $pages = $this->container->db->get('t_facebook_page_events p', null, $sloupce);
        if ($this->container->db->count > 0) {
            foreach ($pages as $page) {
                $fb_pages_pole[] = '<a href="'.$this->container->router->pathFor('fbevents.page', ['id' => $page['c_id']]).'">'.$page['c_fb_name'].'</a>';
            }
        }
        
        $fb_pages = implode(', ', $fb_pages_pole);
        
        // zjistime jestli je vlozeny jako vektor
        $this->container->db->where('c_source_table', 't_facebook_events');
        $this->container->db->where('c_source_object', $event['c_uid']);
        $vektor = $this->container->db->getOne('t_vectors');
        
        if (isset($vektor['c_uid']) and !empty($vektor['c_data'])) {
            $vektor_vystup = '<span class="pull-left"><a href="">is a Vector</a> | <a href="">check data</a></span>';
        }
        else {
            $vektor_vystup = '<button type="submit" class="btn btn-primary">Turn into Vector</button>';
        }
        
        return $this->container->view->render($response, 'fbevents/event.twig',
            array(
                'event' => $event,
                'event_json' => $event_json,
                'fb_pages' => $fb_pages,
                'source_json' => print_r($event_json, true),
                'vector_info' => $vektor_vystup
            )
        );
    }
    // $event_json['description']
    
    // private funkce na zpracovani jedne stranky eventu
    private function updatePageEvents($events_data, $page_id) {
        $last_start_time = 0;
        foreach ($events_data as $event_node) {
            $event_id = $event_node['id'];
            // $event_node['start_time'] je datetime objekt, musime z nej ziskat timestamp pomoci datetime objektove funkce
            if (isset($event_node['start_time'])) { $last_start_time = $event_node['start_time']->getTimestamp(); }
            
            $event_data_json = $event_node->asJson();
            
            // zjistime, jestli ho uz nemame stazeny, pak update
            $this->container->db->where('c_event_id', $event_id);
            $nacteny_event = $this->container->db->getOne('t_facebook_events');
            if (isset($nacteny_event['c_uid'])) {  // update
                $aktualni_event_uid = $nacteny_event['c_uid'];
                $this->container->db->where('c_event_id', $event_id);
                $data = Array ("c_data" => $event_data_json, "c_new" => 0, "c_downloaded" => time() );
                $update = $this->container->db->update('t_facebook_events', $data);
            }
            else {  // insert noveho
                $data = Array ("c_event_id" => $event_id, "c_data" => $event_data_json, "c_new" => 1, "c_downloaded" => time() );
                $aktualni_event_uid = $this->container->db->insert('t_facebook_events', $data);
            }
            
            // zjistime jestli ho mame prirazeny k vybrane strance
            $this->container->db->where('c_page_id', $page_id);
            $this->container->db->where('c_event_uid', $event_id);
            $this->container->db->get('t_facebook_page_events');
            if ($this->container->db->count == 0) {
                $data = Array ("c_page_id" => $page_id, "c_event_uid" => $aktualni_event_uid );
                $insert = $this->container->db->insert('t_facebook_page_events', $data);
            }
        }
        
        return $last_start_time;
    }
    
    // download events on one page
    public function fbeventsPageUpdate($request, $response, $args)
    {
        $page_id = $args['id'];
        $this->container->db->where('c_id', $page_id);
        $page = $this->container->db->getOne('t_facebook_pages');
        $token_id = $page['c_token_id'];
        
        // dokdy stahovat
        $dokdy_stahovat = 0;
        if ($page['c_max_months'] > 0) { $dokdy_stahovat = time() - 3600 * 24 * 30 * $page['c_max_months']; }
        
        // pokud stahujeme upcoming, musime dodat retezec since=now. ev_type=1 expired, ev_type=2 upcoming
        $ev_type = $request->getParam('ev_type');
        $query_cancour = '';
        if ($ev_type == 2) { $query_cancour = '&since=now'; }
        
        // pripravime si pripojeni
        // nacteme si tri hodnoty z t_facebook_tokens, dulezite pro pripojeni pres facebook sdk
        $sloupce = array("c_data->>'$.app_id' as app_id", "c_data->>'$.app_secret' as app_secret", "c_data->>'$.token' as token");
        $this->container->db->where('c_id', $token_id);
        $token_data = $this->container->db->getOne('t_facebook_tokens', $sloupce);
        
        // pripojime se pres aplikaci
        $fb = new \Facebook\Facebook([
          'app_id' => $token_data['app_id'],
          'app_secret' => $token_data['app_secret'],
          'default_graph_version' => 'v2.12'
        ]);
        
        // nacteme si eventy pro tu stranku (defaultne 25 a bud dopredu nebo dozadu v case, podle vybraneho radia,
        // limit lze nastavit prakticky neomezene napr &limit=500 ale fb to pry omezuje podle mnozstvi dat v neurcitou chvili. takze nejlepsi praxe je drzet se defaultu a strankovat)
        // id tam bude vzdy, pridame nasledujici fieldy
        // name,owner,category,description,start_time,end_time,place,cover,updated_time,parent_group
        // attending_count,declined_count,interested_count,maybe_count
        // is_canceled,is_page_owned,event_times,ticket_uri,ticket_uri_start_sales_time
        // parent_group vyzaduje fb schvaleni
        try {
          $fbresponse = $fb->get(
            '/'.$page['c_fb_id'].'/events?fields=name,owner,category,description,start_time,end_time,place,cover,updated_time,attending_count,declined_count,interested_count,maybe_count,is_canceled,is_page_owned,event_times,ticket_uri,ticket_uri_start_sales_time'.$query_cancour.'&limit=27',
            $token_data['token']
          );
        } catch(Facebook\Exceptions\FacebookResponseException $e) {
            $this->container->flash->addMessage('error', 'graph failed: '.$e->getMessage());
            return $response->withRedirect($this->container->router->pathFor('fbevents.main'));
        } catch(Facebook\Exceptions\FacebookSDKException $e) {
            $this->container->flash->addMessage('error', 'facebook sdk failed: '.$e->getMessage());
            return $response->withRedirect($this->container->router->pathFor('fbevents.main'));
        }
        $events_data = $fbresponse->getGraphEdge();
        //$this->container->flash->addMessage('info', 'vraceno: '.print_r($fbresponse, true));
        //$next_cursor = $events_data->getNextCursor();
        $pocet_eventu = count($events_data);
        
        $last_start_time1 = $this->updatePageEvents($events_data, $page_id);
        
        $jeste_stahuj = false;
        if ($events_data != null and $last_start_time1 > $dokdy_stahovat) { $jeste_stahuj = true; }
        
        // stahujem dalsi, dokud neni null nebo dokud nejsme pod hranici mesicu
        $ii = 0;
        while ($jeste_stahuj) {
            $ii++;
            if ($ii > 10) { break; }    // dame tu pro jistotu nejake omezeni
            $events_data = $fb->next($events_data);
            $jeste_stahuj = false;
            if ($events_data != null) {
                $pocet_eventu += count($events_data);
                $last_start_time = $this->updatePageEvents($events_data, $page_id);
                if ($last_start_time > $dokdy_stahovat) { $jeste_stahuj = true; }
            }
        }
        
        $this->container->flash->addMessage('info', $pocet_eventu.' events were updated or inserted.');
        
        return $response->withRedirect($this->container->router->pathFor('fbevents.page', ['id' => $page_id]));
    }
    
    // add page form
    public function addPageForm($request, $response)
    {
        // nacteme si mozne token sety
        $token_options = '';
        $tokens = $this->container->db->get('t_facebook_tokens');
        if ($this->container->db->count > 0) {
            foreach ($tokens as $token) {
                $token_options .= '<option value="'.$token['c_id'].'">'.$token['c_title'].'</option>';
            }
        }
        
        return $this->container->view->render($response, 'fbevents/addpage.twig',
            array(
                'token_options' => $token_options
            )
        );
    }
    
    // add page action
    public function addPageAction($request, $response)
    {
        $token_id = $request->getParam('token_id');
        $page_something = $request->getParam('page_something');
        
        // nacteme si tri hodnoty z t_facebook_tokens, dulezite pro pripojeni pres facebook sdk
        $sloupce = array("c_data->>'$.app_id' as app_id", "c_data->>'$.app_secret' as app_secret", "c_data->>'$.token' as token");
        $this->container->db->where('c_id', $token_id);
        $token_data = $this->container->db->getOne('t_facebook_tokens', $sloupce);
        
        // pripojime se pres aplikaci
        $fb = new \Facebook\Facebook([
          'app_id' => $token_data['app_id'],
          'app_secret' => $token_data['app_secret'],
          'default_graph_version' => 'v2.12'
        ]);
        
        // zkusime stranku stahnout, s pouzitim tokenu
        try {
          $fbresponse = $fb->get(
            '/'.$page_something,
            $token_data['token']
          );
        } catch(Facebook\Exceptions\FacebookResponseException $e) {
            $this->container->flash->addMessage('error', 'graph failed: '.$e->getMessage());
            return $response->withRedirect($this->container->router->pathFor('fbevents.main'));
        } catch(Facebook\Exceptions\FacebookSDKException $e) {
            $this->container->flash->addMessage('error', 'facebook sdk failed: '.$e->getMessage());
            return $response->withRedirect($this->container->router->pathFor('fbevents.main'));
        }
        $pageData = $fbresponse->getGraphNode();
        
        // mel by vratit neco takoveho
        /*
{
  "name": "Industra Art",
  "id": "438212066334138"
}
        */
        
        // podivame se jestli to vratil, TODO
        
        // pripravime si data
        $page_data_json = $pageData->asJson();
        
        // vlozime
        $data = Array ("c_token_id" => $token_id, "c_fb_id" => $pageData['id'], "c_fb_name" => $pageData['name'], "c_data" => $page_data_json );
        $insert = $this->container->db->insert('t_facebook_pages', $data);
        
        
        
        /*
        $token_array = array(
            "app_id" => "943888169109322",
            "app_secret" => "22b1a0cb2437d7ae6a570dc1be750fba",
            "token" => "EAANadhcHB0oBAFd7tnT6WIGupqfhpk63QFpCzbI3mpmVRVUoKiRTX0ddtUBXB88w3ioZBobbXMDs0s71ZB9AMGtEgpZAc47LxfOMYZCCW2TvbbM8QGUk5SxcIYoND9BwBM6t11egbY7v4nTM5yDQsHfcf8qgk3oZD"
        );
        
        $token_json = json_encode($token_array);
        
        $this->container->db->where('c_id', 1);
        $this->container->db->update('t_facebook_tokens', Array ( 'c_data' => $token_json ));
        */
        
        
        $this->container->flash->addMessage('info', 'page '.$pageData['name'].' was added or not: '.$this->container->db->getLastError());
        
        return $response->withRedirect($this->container->router->pathFor('fbevents.main'));
    }
    
    // add token form. tady snad nic zvlastniho nebude. jen pak overeni ze jsem root ci admin
    public function addTokenForm($request, $response)
    {
        return $this->container->view->render($response, 'fbevents/addtoken.twig');
    }
    
    // add token action
    public function addTokenAction($request, $response)
    {
        $token_title = $request->getParam('title');
        $app_id = $request->getParam('app_id');
        $app_secret = $request->getParam('app_secret');
        $token = $request->getParam('token');
        
        $token_array = array(
            "app_id" => $app_id,
            "app_secret" => $app_secret,
            "token" => $token
        );
        $token_json = json_encode($token_array);
        
        // vlozime
        $data = Array ("c_title" => $token_title, "c_data" => $token_json);
        $insert = $this->container->db->insert('t_facebook_tokens', $data);
        
        $this->container->flash->addMessage('info', 'new token credentials were added under name '.$token_title);
        
        return $response->withRedirect($this->container->router->pathFor('fbevents.main'));
    }
    
    // edit token form, musim poskytnout hodnoty do sablony
    public function editTokenForm($request, $response, $args)
    {
        $token_id = $args['id'];
        $this->container->db->where('c_id', $token_id);
        $token = $this->container->db->getOne('t_facebook_tokens');
        $token_data = json_decode($token['c_data']);
        
        return $this->container->view->render($response, 'fbevents/edittoken.twig',
            array(
                'token_id' => $token_id,
                'token_title' => $token['c_title'],
                'token_data' => $token_data
            )
        );
    }
    
    // edit token action
    public function editTokenAction($request, $response, $args)
    {
        $token_id = $args['id'];
        
        $token_title = $request->getParam('title');
        $app_id = $request->getParam('app_id');
        $app_secret = $request->getParam('app_secret');
        $token = $request->getParam('token');
        
        $token_array = array(
            "app_id" => $app_id,
            "app_secret" => $app_secret,
            "token" => $token
        );
        $token_json = json_encode($token_array);
        
        // vlozime
        $data = Array ("c_title" => $token_title, "c_data" => $token_json);
        $this->container->db->where('c_id', $token_id);
        $update = $this->container->db->update('t_facebook_tokens', $data);
        
        if ($update) {
            $this->container->flash->addMessage('info', 'token id '.$token_id.' was updated');
        }
        else {
            $this->container->flash->addMessage('info', 'update of token id '.$token_id.' failed. '.$this->container->db->getLastError());
        }
        
        return $response->withRedirect($this->container->router->pathFor('fbevents.main'));
    }
    
    // edit page form, v podstate jen pocet mesicu do minulosti
    public function editPageForm($request, $response, $args)
    {
        $page_id = $args['id'];
        $this->container->db->where('c_id', $page_id);
        $page_data = $this->container->db->getOne('t_facebook_pages');
        
        $token_options = '';
        $tokens = $this->container->db->get('t_facebook_tokens');
        if ($this->container->db->count > 0) {
            foreach ($tokens as $token) {
                $token_options .= '<option value="'.$token['c_id'].'" '.($token['c_id'] == $page_data['c_token_id']?'selected':'').'>'.$token['c_title'].'</option>';
            }
        }
        
        $optiony = '';
        if ($page_data['c_max_months'] == 0) { $optiony .= '<option value="0" selected>all past events</option>'; }
        else { $optiony .= '<option value="0">all past events</option>'; }
        for ($i = 1; $i <= 12; $i++) {
            if ($page_data['c_max_months'] == $i) { $optiony .= '<option value="'.$i.'" selected>'.$i.' month'.($i > 1?'s':'').' old</option>'; }
            else { $optiony .= '<option value="'.$i.'">'.$i.' month'.($i > 1?'s':'').' old</option>'; }
        }
        
        return $this->container->view->render($response, 'fbevents/editpage.twig',
            array(
                'page_id' => $page_id,
                'page_data' => $page_data,
                'options' => $optiony,
                'token_options' => $token_options
            )
        );
    }
    
    // edit page action
    public function editPageAction($request, $response, $args)
    {
        $page_id = $args['id'];
        
        $max_months = $request->getParam('max_months');
        $token_id = $request->getParam('token_id');
        
        // vlozime
        $data = Array ("c_token_id" => $token_id, "c_max_months" => $max_months);
        $this->container->db->where('c_id', $page_id);
        $update = $this->container->db->update('t_facebook_pages', $data);
        
        if ($update) {
            $this->container->flash->addMessage('info', 'page id '.$page_id.' was updated');
        }
        else {
            $this->container->flash->addMessage('info', 'update of page id '.$page_id.' failed. '.$this->container->db->getLastError());
        }
        
        return $response->withRedirect($this->container->router->pathFor('fbevents.main'));
    }
    
    // vectorize event
    public function fbeventVectorize($request, $response, $args)
    {
        $event_uid = $args['id'];
        
        $this->container->db->where('c_uid', $event_uid);
        $event = $this->container->db->getOne('t_facebook_events');
        
        $event_json = json_decode($event['c_data'], true);  // vytvori array, ktery budeme vektorizovat
        
        // vlozime to zatim prazdne, abychom ziskali vygenerovane autoincrement id
        $data = Array ("c_source_table" => 't_facebook_events', "c_source_object" => $event_uid);
        $vector_id = $this->container->db->insert('t_vectors', $data);
        
        $now_iso_date = date('c');
        
        $vector = array();
        $vector['data']['_v'] = 1;
        $vector['data']['uid'] = $vector_id;
        $vector['data']['classification'] = 'event';
        $vector['data']['tzid'] = 'Europe/Prague';
        $vector['data']['dt_created'] = $now_iso_date;
        $vector['data']['dt_modified'] = $now_iso_date;
        $vector['data']['sequence'] = 1;
        $vector['data']['attr']['feed'] = true;
        
        $vector['data']['status']['privacy'] = 'public';
        $vector['data']['status']['published'] = true;
        $vector['data']['status']['dt_infeed'] = '';
        
        // summary je pole, protoze muzou byt jine jazyky
        $vector['data']['summary'] = array();
        $vector['data']['summary'][0] = array('data' => $event_json['name'], 'lang' => 'cs', 'sequence' => 1);
        
        // description je pole, protoze muzou byt jine jazyky
        $vector['data']['description'] = array();
        $vector['data']['description'][0] = array('data' => $event_json['description'], 'lang' => 'cs', 'sequence' => 1);
        
        $vector['data']['dt_start'] = $event_json['start_time'];
        $vector['data']['dt_end'] = $event_json['end_time'];
        
        if (isset($event_json['cover'])) {
            $vector['data']['attach'] = array();
            $vector['data']['attach'][0] = array('uri' => $event_json['cover']['source'], 'cover' => true);
        }
        
        // tady jeste doladit, mozna zmenit strukturu, protoze neni jasne co ma byt pole
        if (isset($event_json['place'])) {
            $vector['data']['location'] = array();
            $pomocny = array();
            $pomocny['place']['name'] = $event_json['place']['name'];
            $pomocny['place']['id'] = 'https://www.facebook.com/'.$event_json['place']['id'];
            if (isset($event_json['place']['location']['latitude'])) {
                $pomocny['geo']['lat'] = $event_json['place']['location']['latitude'];
                $pomocny['geo']['lon'] = $event_json['place']['location']['longitude'];
            }
            if (isset($event_json['place']['location']['city'])) {
                $pomocny['adr']['locacity'] = $event_json['place']['location']['city'];
                $pomocny['adr']['code'] = $event_json['place']['location']['zip'];
                $pomocny['adr']['country'] = $event_json['place']['location']['country'];
                $pomocny['adr']['street'] = $event_json['place']['location']['street'];
            }
            $vector['data']['location'][] = $pomocny;
        }
        
        // vlastnici, ale nasi a to nemame ulozeno
        
        // pocty ucastniku
        $vector['data']['response_stats'] = array();
        $vector['data']['response_stats'][] = array('type' => 'participating', 'count' => $event_json['attending_count']); // attending_count
        $vector['data']['response_stats'][] = array('type' => 'considering', 'count' => $event_json['maybe_count']); // maybe_count
        $vector['data']['response_stats'][] = array('type' => 'declined', 'count' => $event_json['declined_count']); // declined_count
        
        // objekt vztahu rel
        $vector['data']['rel']['name'] = 'Facebook event';
        $vector['data']['rel']['type'] = 'facebook event';
        $vector['data']['rel']['uri'] = 'https://www.facebook.com/'.$event['c_event_id'];
        $vector['data']['rel']['proxy_src'] = 't_facebook_events';
        $vector['data']['rel']['proxy_obj'] = $event_uid;
        
        
        $vector_json = json_encode($vector);
        
        // updatujeme
        $data = Array ("c_data" => $vector_json);
        $this->container->db->where('c_uid', $vector_id);
        $update = $this->container->db->update('t_vectors', $data);
        
        if ($update) {
            $this->container->flash->addMessage('info', 'this event was vectorized');
        }
        else {
            $this->container->flash->addMessage('info', 'vectorization of this event failed. '.$this->container->db->getLastError());
        }
        
        return $response->withRedirect($this->container->router->pathFor('fbevents.event', ['id' => $event_uid]));
    }
    
    
    // fb login
    public function fblogin($request, $response)
    {
        return $this->container->view->render($response, 'fbevents/fblogin.twig');
    }
    
    // privacy policy 
    public function privacyPolicy($request, $response)
    {
        return $this->container->view->render($response, 'fbevents/privacy.twig');
    }
    
    // terms and services
    public function termsAndServices($request, $response)
    {
        return $this->container->view->render($response, 'fbevents/terms.twig');
    }
    
}
