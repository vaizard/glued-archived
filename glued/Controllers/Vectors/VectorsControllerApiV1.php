<?php
namespace Glued\Controllers\Vectors;

use Glued\Controllers\Controller;

class VectorsControllerApiV1 extends Controller
{
    
    // nejprve pro pristup zvenci k vektorum
    
    // nase vnitrni funkce, ktera zajistuje bezpecny vypis json dat
    private function respond($response,$content = '', $httpStatus = 200, $contentType = 'application/json')
    {
        $body = $response->getBody();
        $body->write($content);
        return $response->withStatus($httpStatus)->withHeader('Content-Type', 'aplication/json')->withBody($body);
    }
    
    
    // funkce co nacte 1 nebo vice eventu a vrati je ve forme jsonu
    // tvar bud 10 nebo 45,47,48 nebo 1,2-5
    // tedy oddeleno carkama a jeste to muze byt rozsah
    // TODO, bude i nejaky token, aby si to nemohl kazdej stahovat
    public function showEvent($request, $response, $args)
    {
        $vektory = $args['ids'];
        $casti = explode(',', $vektory);
        $pole_vektor_id = array();
        foreach ($casti as $cast) {
            if (substr_count($cast, '-') == 1) {
                $rozsahy = explode('-', $cast);
                $zacatek = (int) $rozsahy[0];
                $konec = (int) $rozsahy[1];
                for ($i = $zacatek; $i <= $konec; $i++) {
                    $pole_vektor_id[] = $i;
                }
            }
            else {
                $pole_vektor_id[] = (int) $cast;
            }
        }
        
        $constructed_vector_array = array();
        
        $this->container->db->where('c_uid', $pole_vektor_id, 'IN');
        $vectors = $this->container->db->get('t_vectors');
        if ($this->container->db->count > 0) {
            foreach ($vectors as $vector) {
                $vector_json = json_decode($vector['c_data'], true);
                // odstranime nektere tajne veci
                unset($vector_json['data']['rel']['proxy_src']);
                unset($vector_json['data']['rel']['proxy_obj']);
                $constructed_vector_array[] = $vector_json['data'];
            }
        }
        
        $final_vector_array['data'] = $constructed_vector_array;
        $final_json = json_encode($final_vector_array);
        
        return $this->respond($response, $final_json);
    }
    
    // provede hledani, podle zadanych get parametru
    public function showList($request, $response)
    {
        $cursor = 0;
        if (isset($_GET['cursor'])) { $cursor = $_GET['cursor']; }
        $limit = 100;
        if (isset($_GET['limit'])) { $limit = $_GET['limit']; }
        $time = '';
        if (isset($_GET['time'])) { $time = $_GET['time']; }
        
        // priprava nacitanych sloupcu a vythnutych dat k porovnani
        $sloupce = array("c_uid", "c_data", "c_data->>'$.data.dt_start' as start_time", "c_data->>'$.data.dt_end' as end_time");
        
        // podminky
        
        // time past|present|future
        $iso_now = date('c');
        if ($time == 'past') { $this->container->db->where("c_data->>'$.data.dt_end'", $iso_now, '<'); }
        if ($time == 'present') {
            $this->container->db->where("c_data->>'$.data.dt_start'", $iso_now, '<');
            $this->container->db->where("c_data->>'$.data.dt_end'", $iso_now, '>');
        }
        if ($time == 'future') { $this->container->db->where("c_data->>'$.data.dt_start'", $iso_now, '>'); }
        
        $vectors = $this->container->db->get('t_vectors', array($cursor, $limit), $sloupce);
        if ($this->container->db->count > 0) {
            foreach ($vectors as $vector) {
                $constructed_vector_array[] = $vector['c_uid'];
            }
        }
        
        $final_vector_array['data']['uid'] = $constructed_vector_array;
        $final_json = json_encode($final_vector_array);
        
        return $this->respond($response, $final_json);
    }
    
    
    // zpracovani pridavani vektoru
    
    // api for add new vector (parametr args neni potreba, post promenna bude v request)
    public function insertVectorApi($request, $response)
    {
        
        $senddata = $request->getParam('billdata');
        $user_id = $_SESSION['user_id'];
        
        // vlozime to jak to prislo z formu
        $data = Array ("c_data" => $senddata);
        $insert = $this->container->db->insert('t_vectors', $data);
        if ($insert) {
            // prepiseme uid a casy
            $this->container->db->rawQuery("UPDATE t_vectors SET c_data = JSON_SET(c_data, '$.data.uid', ?, '$.data.dt_created', ?) WHERE c_uid = ? ", Array (strval($insert), date("Y-m-d H:i:s"), $insert));
            
            if ($this->container->db->getLastErrno() === 0) {
                $response->getBody()->write('ok');
            }
            else {
                $response->getBody()->write('update failed: ' . $this->container->db->getLastError());
            }
        }
        else {
            $response->getBody()->write('error');
        }
        
        // vratime prosty text
        return $response;
        
    }
    
    
}
