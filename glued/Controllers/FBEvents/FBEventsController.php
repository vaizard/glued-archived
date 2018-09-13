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
                    <td><a href="'.$this->container->router->pathFor('fbevents.page', ['id' => $page['c_id']]).'">show events</a> <a href="">edit page</a></td>
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
                
                $events_output .= '<tr>
                    <td>'.$event['c_uid'].'</td>
                    <td>'.$event['c_event_id'].'</td>
                    <td>'.$event['name'].'</td>
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
    
    // private funkce na zpracovani jedne stranky eventu
    private function updatePageEvents($events_data, $page_id) {
        foreach ($events_data as $event_node) {
            $event_id = $event_node['id'];
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
    }
    
    // download events on one page
    public function fbeventsPageUpdate($request, $response, $args)
    {
        $page_id = $args['id'];
        $this->container->db->where('c_id', $page_id);
        $page = $this->container->db->getOne('t_facebook_pages');
        $token_id = $page['c_token_id'];
        
        // pokud stahujeme upcoming, musime dodat retezec since=now. ev_type=1 expired, ev_type=2 upcoming
        $ev_type = $request->getParam('ev_type');
        $query_cancour = '';
        if ($ev_type == 2) { $query_cancour = '?since=now'; }
        
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
        
        // nacteme si eventy pro tu stranku (defaultne 25 a bud dopredu nebo dozadu v case, podle vybraneho radia)
        try {
          $fbresponse = $fb->get(
            '/'.$page['c_fb_id'].'/events'.$query_cancour,
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
        //$next_cursor = $events_data->getNextCursor();
        
        $this->updatePageEvents($events_data, $page_id);
        
        // zkusime dalsi stranku
        $next_events_data = $fb->next($events_data);
        if ($next_events_data != null) {
            $this->updatePageEvents($next_events_data, $page_id);
        }
        
        /*
        foreach ($events_data as $event_node) {
            $event_id = $event_node['id'];
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
        */
        
        
        $this->container->flash->addMessage('info', count($events_data).' events were updated. Then another '.count($next_events_data).' events were updated.');   // next cursor is '.$next_cursor.'. ciste pole dat: '.print_r($events_data, true)
        
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
    
}
