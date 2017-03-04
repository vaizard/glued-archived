<?php

namespace Glued\Controllers\Api\v0_1;
use Glued\Controllers\Controller;
use Jsv4\Validator as jsonv;

// TODO presenters for responses and errors
// TODO consider using fractal
// TODO add filtering, typecasting, etc.
// TODO put responder() into a common controller for apis

class TimePixelsController extends Controller
{
    public function get($request, $response, $args)
    {
        // TODO filter the input
        $this->container->db->where('id', $args['id']);
        $timepixel = $this->container->db->getOne("timepixels");

        if (!$timepixel) {
            return $this->respond($response, null, 404);
        }

        $data['data'] = json_decode($timepixel['json'], true);
        $data['data']['id'] = (string) $timepixel['id'];
        
        // nacteni useru
        $data['data']['users'] = array();
        $this->container->db->join("t_users u", "r.user_id=u.c_uid", "LEFT");
        $this->container->db->where('r.timepixel_id', $args['id']);
        $users = $this->container->db->get("rel_timepixels_users r", null, "u.c_uid, u.c_screenname");
        if ($this->container->db->count > 0) {
            foreach ($users as $user) {
                $data['data']['users'][] = array('id' => $user['c_uid'], 'name' => $user['c_screenname']);
            }
        }
        
        return $this->respond($response,json_encode($data), 200, 'application/json');
    }


    public function post($request, $response, $args)
    {
        $payload = $request->getParsedBody();

        // TODO do propper filtering, checks on garbage or 
        // on-insert-umutable data (i.e. the id is autoincremented)
        // this is just a test of the 400 response

/*new*/
         //$schema = file_get_contents('/var/www/html/glued/glued/Controllers/Api/v0_1/schemas/timepixels.json');
         //print_r($schema);
         //takze normalne pouzij $jsonvr = jsonv::isValid(json_decode($payload), json_decode($schema));
         $jsonvr = jsonv::isValid($payload, json_decode($schema));
         print_r($jsonvr);
         exit;
/*newend*/


/*old
        if (!isset($payload['title'], $payload['dt_start'])) {
            $data['message'] = 'Title and body required';
            $data['errcode'] = 1;
            return $this->respond($response, json_encode($data), 400);
        }
oldend*/


        $data = [ 'json' => json_encode($payload) ];
        $id = $this->container->db->insert('timepixels', $data);
        $payload['id'] = (string) $id;
        return $this->respond($response,json_encode($payload), 201, 'application/json');
    }


    public function delete($request, $response, $args)
    {
        // nacteme si idecko
        $this->container->db->where('id', $args['id']);
        $timepixel = $this->container->db->getOne("timepixels");
        
        // pokud neexistuje, vratime ze to neexistuje
        if (!$timepixel) {
            return $this->respond($response, null, 404);
        }
        
        // smazani a priprava vystupu
        $this->container->db->where('id', $args['id']);
        if($this->container->db->delete('timepixels')) {
            $data['message'] = 'id '.$args['id'].' deleted succesfully';
            $response_code = 200;
        }
        else {  // nastane, jen pokud je nejaka chyba v pripojeni k db, protoze neexistenci zaznamu uz podchyti ta podminka vyse
            $data['message'] = 'error: id '.$args['id'].' cannot be deleted';
            $response_code = 400;
        }
        
        return $this->respond($response,json_encode($data), $response_code, 'application/json');
    }

    public function respond($response,$content = '', $httpStatus = 200, $contentType = 'application/json')
    {
        $body = $response->getBody();
        $body->write($content);
        return $response->withStatus($httpStatus)->withHeader('Content-Type', 'aplication/json')->withBody($body);
    }

}
