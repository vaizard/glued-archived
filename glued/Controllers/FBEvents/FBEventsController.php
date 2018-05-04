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
                
                $sloupce = array("c_uid");
                $this->container->db->where('c_page_id', $page['c_id']);
                $this->container->db->get('t_facebook_events', null, $sloupce);
                
                $pages_output .= '<tr>
                    <td>'.$page['c_id'].'</td>
                    <td>'.$page['c_fb_id'].'</td>
                    <td>'.$page['c_fb_name'].'</td>
                    <td>'.$this->container->db->count.'</td>
                    <td><a href="'.$this->container->router->pathFor('fbevents.page', ['id' => $page['c_id']]).'">show events</a></td>
                </tr>';
            }
        }
        
        
        return $this->container->view->render($response, 'fbevents/gui.twig',
            array(
                'pages_output' => $pages_output
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
        $sloupce = array("c_uid", "c_event_id", "c_data->>'$.name' as name", "c_data->>'$.start_time' as start_time");
        $this->container->db->where('c_page_id', $page_id);
        $this->container->db->where('c_new', 1);
        $events = $this->container->db->get('t_facebook_events', null, $sloupce);
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
    
    // download events on one page
    public function fbeventsPageUpdate($request, $response, $args)
    {
        $page_id = $args['id'];
        $this->container->db->where('c_id', $page_id);
        $page = $this->container->db->getOne('t_facebook_pages');
        $token_id = $page['c_token_id'];
        
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
        
        // nacteme si eventy pro tu stranku
        try {
          $fbresponse = $fb->get(
            '/'.$page['c_fb_id'].'/events',
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
        
        foreach ($events_data as $event_node) {
            $event_id = $event_node['id'];
            $event_data_json = $event_node->asJson();
            
            // pripadnym co tam mame dame c_new = 0
            $this->container->db->where('c_event_id', $event_id);
            $data = Array ("c_new" => 0 );
            $update = $this->container->db->update('t_facebook_events', $data);
            
            // insert noveho
            $data = Array ("c_page_id" => $page_id, "c_event_id" => $event_id, "c_data" => $event_data_json, "c_new" => 1, "c_downloaded" => time() );
            $insert = $this->container->db->insert('t_facebook_events', $data);
            
        }
        
        $this->container->flash->addMessage('info', count($events_data).' events were updated.'.print_r($fbresponse, true));
        
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
    
    
    
}
