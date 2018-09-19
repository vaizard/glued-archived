<?php
namespace Glued\Controllers\FBEvents;

use Glued\Controllers\Controller;

class FBEventsControllerApiV1 extends Controller
{
    // prehozeno do Vectors, ale treba tu taky bude nejake api, takze soubor controleru nechavam
    
    /*
    // nase vnitrni funkce, ktera zajistuje bezpecny vypis json dat
    private function respond($response,$content = '', $httpStatus = 200, $contentType = 'application/json')
    {
        $body = $response->getBody();
        $body->write($content);
        return $response->withStatus($httpStatus)->withHeader('Content-Type', 'aplication/json')->withBody($body);
    }
    
    
    // funkce co nacte 1 event a vrati ho ve forme jsonu
    // TODO, bude i nejaky token, aby si to nemohl kazdej stahovat
    public function showEvent($request, $response, $args)
    {
        $vektor_id = $args['id'];
        $this->container->db->where('c_uid', $vektor_id);
        $event = $this->container->db->getOne('t_vectors');
        
        return $this->respond($response,$event['c_data']);
    }
    */
}
