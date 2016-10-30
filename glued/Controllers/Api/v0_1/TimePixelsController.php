<?php

namespace Glued\Controllers\Api\v0_1;
use Glued\Controllers\Controller;

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
            return $this->respond(null, 404);
        }

        $data['data'] = json_decode($timepixel['json'], true);
        $data['data']['id'] = (string) $timepixel['id']; 

        return $this->respond($response,json_encode($data), 200, 'application/json');
    }


    public function post($request, $response, $args)
    {
        $payload = $request->getParsedBody();

        // TODO do propper filtering, checks on garbage or 
        // on-insert-umutable data (i.e. the id is autoincremented)
        // this is just a test of the 400 response
        if (!isset($payload['title'], $payload['dt_start'])) {
            return $this->respond($response, ['message' => 'Title and body required', 'errcode' => '1'], 400);
        }

        $data = [ 'json' => json_encode($payload) ];
        $id = $this->container->db->insert('timepixels', $data);
        $payload['id'] = (string) $id;
        return $this->respond($response,json_encode($payload), 201, 'application/json');
    }




    public function respond($response,$content = '', $httpStatus = 200, $contentType = 'application/json')
    {
        $body = $response->getBody();
        $body->write($content);
        return $response->withStatus($httpStatus)->withHeader('Content-Type', 'aplication/json')->withBody($body);
    }

}
