<?php

namespace Glued\Controllers\Barcode;
use Glued\Controllers\Controller;

class BarcodeController extends Controller
{
    
    // shows basic page with all stock
    public function barCode($request, $response)
    {
        $vystup = '';
        /*
        $generator = new barcode_generator();
        
        $image = $generator->render_image($_GET['s'], $_GET['d'], $options);
        imagepng($image);
        imagedestroy($image);
        */
        
        return $this->container->view->render($response, 'barcode/gui.twig', array('vystup' => $vystup));
    }
    
    
    
}
