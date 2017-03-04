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
    
}
