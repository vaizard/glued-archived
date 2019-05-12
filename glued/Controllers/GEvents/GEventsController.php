<?php

namespace Glued\Controllers\GEvents;
use Glued\Controllers\Controller;

class GEventsController extends Controller
{
    
    // shows pages
    public function geventsMain($request, $response)
    {
        // zatim tu nic nebude
        return $this->container->view->render($response, 'gevents/gui.twig', array(
            'ui_menu_active' => 'gevents'
        ));
    }
    
}
