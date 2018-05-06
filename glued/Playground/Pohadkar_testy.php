<?php

namespace Glued\Playground;
use Glued\Controllers\Controller;

class Pohadkar_testy extends Controller
{
    
    
    // funkce ktera vypise formular na vlozeni nove platby
    public function form($request, $response)
    {
        return $this->container->view->render($response, 'pg-testy.twig');
    }
    
    
    // funkce, ktera vypise prikazovy soubor, pro jednu platbu
    public function test($request, $response, $args)
    {
        $vystup = '<h3>test innodb</h3>';
        
        function microtime_float()
        {
            list($usec, $sec) = explode(" ", microtime());
            return ((float)$usec + (float)$sec);
        }
        
        $zacatek = microtime_float();
        
        for ($i1 = 1; $i1 <= 30; $i1++) {
            for ($i2 = 1; $i2 <= 10; $i2++) {
                $hodnota = rand(2000, 8000);
                
                $this->container->db->where("id1", $i1);
                $this->container->db->where("id2", $i2);
                $data = $this->container->db->getOne('sh_pokus');
                
                
                if (isset($data['id1']) and $data['id1'] > 0) {
                    
                    //$vystup .= '<div>ctu: '.print_r($data, true).'</div>';
                    
                    // provedeme update
                    
                    // stejna hodnota
                    //$nova_hodnota = $data['hodnota'];
                    $nova_hodnota = rand(5000, 21000);
                    
                    $data = Array (
                        'hodnota' => $nova_hodnota
                    );
                    $this->container->db->where("id1", $i1);
                    $this->container->db->where("id2", $i2);
                    $this->container->db->update ('sh_pokus', $data);
                    
                    
                }
                
                
                
                
                
            }
        }
        
        $konec = microtime_float();
        
        $vystup .= '<div>300 updatu trvalo: '.($konec - $zacatek).' s</div>';
        
        return $this->container->view->render($response, 'pg-testy.twig', array('vystup' => $vystup));
    }
    
    
    // funkce ktera se pripoji k fb za pomoci sdk a stahne neco
    public function sdkindustry($request, $response)
    {
        
        $vystup = '';
        
        // moje aplikace Glued events
        
        //tokeny ziskaveme pres Graph API Explorer
        
        // obecny token aplikace, platny zrejme nekonecne dlouho
        //$token = '943888169109322|QxiL-5Z0Jwa9GLzbztVaHoV03q4';
        // o tento token pozadala aplikace vlastnika jednoho eventu (eventu lalala), platnost bude asi hodinu, takze dalsi dny uz by nemel fungovat
        //$token = 'EAANadhcHB0oBAE3EK5TwGiqZClCbRIlaNXeqkRrExHWHXqX8ILcEzNjb55lnWAT4kZCPJhgObZCtVnoqZBMCspsiVJhl2kZAf6ymq7ZCpVLZC8eGDpRuWx1UPGppvMZCiN4K4JF6sLcTDk1bFZAWPixqP6D9nXZCdvuiD3JmByQiEAURdHl8d3naMSHcRDoDnYjPAlNH72KK81QHEwffAD23NTAnx1u4zgitpZCeKyqIq6FZAQZDZD';
        
        // token pro nacteni attendantu jednoho konkretniho eventu z industry, prodlouzeny na 2 mesice
        $token = 'EAANadhcHB0oBAFd7tnT6WIGupqfhpk63QFpCzbI3mpmVRVUoKiRTX0ddtUBXB88w3ioZBobbXMDs0s71ZB9AMGtEgpZAc47LxfOMYZCCW2TvbbM8QGUk5SxcIYoND9BwBM6t11egbY7v4nTM5yDQsHfcf8qgk3oZD';
        
        
        // v2.10
        $fb = new \Facebook\Facebook([
          'app_id' => '943888169109322',
          'app_secret' => '22b1a0cb2437d7ae6a570dc1be750fba',
          'default_graph_version' => 'v2.12'
          //'default_access_token' => '{access-token}', // optional
        ]);
        
        // 2024171704523996 - vysivane napisy
        // 2010135479301940 - Ukradený èas
        
        //$industra_event = '2024171704523996';
        $industra_event = '1734810199900434';   // glued event
        //$industra_event = '2015999335322076';   // glued opakovana udalost - nejde nacist
        
        $vystup .= '<h2>attendanti eventu '.$industra_event.' pomoci sdk graph api</h2>';
        try {
          // Returns a `Facebook\FacebookResponse` object
          $fbresponse = $fb->get(
            '/'.$industra_event.'/attending',
            $token
          );
          // 
        } catch(Facebook\Exceptions\FacebookResponseException $e) {
          echo 'Graph returned an error: ' . $e->getMessage();
          exit;
        } catch(Facebook\Exceptions\FacebookSDKException $e) {
          echo 'Facebook SDK returned an error: ' . $e->getMessage();
          exit;
        }
        $graphEdge = $fbresponse->getGraphEdge();
        
        // Iterate over all the GraphNode's returned from the edge
        foreach ($graphEdge as $graphNode) {
            $vystup .= '<div style="margin: 30px 10px;">'.print_r($graphNode, true).'</div>';
        }
        
        
        
        $vystup .= '<h2>stazene eventy z industry pomoci sdk graph api</h2>';
        try {
          // Returns a `Facebook\FacebookResponse` object
          $fbresponse = $fb->get(
            '/115212861982246/events?fields=name,updated_time',
            $token
          );
          // 
        } catch(Facebook\Exceptions\FacebookResponseException $e) {
          echo 'Graph returned an error: ' . $e->getMessage();
          exit;
        } catch(Facebook\Exceptions\FacebookSDKException $e) {
          echo 'Facebook SDK returned an error: ' . $e->getMessage();
          exit;
        }
        $graphEdge = $fbresponse->getGraphEdge();
        
        // Iterate over all the GraphNode's returned from the edge
        foreach ($graphEdge as $graphNode) {
            $vystup .= '<div style="margin: 30px 10px;">'.print_r($graphNode, true).'</div>';
        }
        
        
        
        // id eventu
        //$id_eventu = '2024171704523996';    // vysivane napisy v industre
        $id_eventu = '1734810199900434';  // lalala
        //$id_eventu = '2015999335322076';  // opakovana udalost
        
        
        $vystup .= '<h2>vse o eventu '.$id_eventu.'</h2>';
        try {
          // Returns a `Facebook\FacebookResponse` object
          $fbresponse = $fb->get(
            '/'.$id_eventu.'?fields=name,description,start_time,end_time,attending_count,declined_count,maybe_count,interested_count,cover',
            $token
          );
        } catch(Facebook\Exceptions\FacebookResponseException $e) {
          echo 'Graph returned an error: ' . $e->getMessage();
          exit;
        } catch(Facebook\Exceptions\FacebookSDKException $e) {
          echo 'Facebook SDK returned an error: ' . $e->getMessage();
          exit;
        }
        $graphNode = $fbresponse->getGraphNode();
        
        $vystup .= '<div style="margin: 30px 10px;">'.print_r($graphNode, true).'</div>';
        
        
        
        
        
        return $this->container->view->render($response, 'sdk-industra.twig', array('vystup' => $vystup));
    }
    
    
    // test kombinace json schema a input mask
    public function schema_mask_test($request, $response)
    {
        // zvlastni pravidla pro vygenerovani jednotlivych prvku
        // v __DIR__ je hodnota aktualniho adresare, takze zde /var/www/html/glued/glued/Controllers/Assets
        // odebrano   "required" : [ "wovat", "vat" ],
        $json_uischema_output = file_get_contents(__DIR__.'/V1/jsonuischemas/form_ui.json');
        
        // schema celeho formulare
        $json_schema_output = file_get_contents(__DIR__.'/V1/jsonschemas/new_assets_form.json');
        
        // zakladni data, momentalne nic, ale musi tam byt aspon prazdny json objekt, tedy ty slozene zavorky
        $json_formdata_output = '{"data":{"ts_created":"'.time().'","ts_updated":"'.time().'"}}';
        
        //$this->container['settings']['glued']['hostname']
        //$this->container->settings->glued->hostname
        // vnitrek onsubmit funkce
        //         alert('xhr status: ' + xhr.status + ', status: ' + status + ', err: ' + err)
        $json_onsubmit_output = '
    $.ajax({
      url: "https://'.$this->container['settings']['glued']['hostname'].$this->container->router->pathFor('assets.api.new').'",
      dataType: "text",
      type: "POST",
      data: "stockdata=" + JSON.stringify(formData.formData),
      success: function(data) {
        // diky replacu nezustava puvodni adresa v historii, takze se to vice blizi redirectu
        // presmerovani na editacni stranku se vraci z toho ajaxu
        window.location.replace(data);
        /*
        ReactDOM.render((<div><h1>Thank you</h1><pre>{JSON.stringify(formData.formData, null, 2) }</pre></div>), 
                 document.getElementById("main"));
        */
      },
      error: function(xhr, status, err) {
        
        ReactDOM.render((<div><h1>Something goes wrong ! not saving.</h1><pre>{JSON.stringify(formData.formData, null, 2) }</pre></div>), 
                 document.getElementById("main"));
      }
    });
        ';
        
        return $this->container->view->render($response, 'playground/test3.twig', array(
            'json_schema_output' => $json_schema_output,
            'json_uischema_output' => $json_uischema_output,
            'json_formdata_output' => $json_formdata_output,
            'json_onsubmit_output' => $json_onsubmit_output
        ));
    }
    
}
