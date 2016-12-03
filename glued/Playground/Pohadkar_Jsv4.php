<?php

namespace Glued\Playground;
use Glued\Controllers\Controller;
use Jsv4\Validator as jsonv;
use Jsv4\SchemaStore;

class Pohadkar_Jsv4 extends Controller
{
    public function validationtest($request, $response)
    {
        echo '<h1>test json schema</h1>';
        $schema = file_get_contents('/var/www/html/glued/glued/Controllers/Api/v0_1/schemas/timepixels.json');
        $payload = '
        { "data":
          {
           "titlex": "New event",
           "dt_start": "2017-02-13 15:00",
           "dt_end": "2017-02-13 16:00"
          }
        }
        ';
        
        $payload2 = '
        { "data":
          {
           "title": "New event",
           "dt_start": "2017-02-13 15:00",
           "dt_end": "2017-02-13 16:00",
           "users": [{
            "id": 5,
            "name": "kuba"
           }]
          }
        }
        ';
        
        //$result = jsonv::validate(json_decode($payload), json_decode($schema));
        
        
        $jsonvr = jsonv::isValid(json_decode($payload), json_decode($schema));
        $jsonvr2 = jsonv::isValid(json_decode($payload2), json_decode($schema));
        //$jsonvr = jsonv::isValid($payload, $schema);
        
        echo '<br /><div>payload je: ('.$payload.')</div>';
        echo '<br /><div>payload2 je: ('.$payload2.')</div>';
        echo '<br /><div>is valid payloadu vraci:</div>';
        var_dump($jsonvr);
        echo '<br /><div>is valid payloadu2 vraci:</div>';
        var_dump($jsonvr2);
        
        
        // pokus cislo 2
        
        echo '<h1>test json schema 2 dle knihovny</h1>';
        /*
        $test_json_schema = '
        {
            "schema": {
                "minLength": 5
            }
        }
        ';
        */
        $test_json_schema = '
        {
            "minLength": 5
        }
        ';
        
        $test_json_data = '
        {
            "data": "tri"
        }
        ';
        
        $test_json_data2 = '
        {
            "data": "je nas tu aspon 5"
        }
        ';
        
        $test_objekt_schema = json_decode($test_json_schema);
        $test_objekt_data = json_decode($test_json_data);
        $test_objekt_data2 = json_decode($test_json_data2);
        
        $validate_result1 = jsonv::isValid($test_objekt_data->data, $test_objekt_schema);
        $validate_result2 = jsonv::isValid($test_objekt_data2->data, $test_objekt_schema);
        
        echo '<div>json schema je: ('.$test_json_schema.')</div>';
        //echo '<div>totez jako objekt:</div>';
        //var_dump($test_objekt_schema);
        
        echo '<div>json data 1 je: ('.$test_json_data.')</div>';
        //echo '<div>totez jako objekt:</div>';
        //var_dump($test_objekt_data);
        
        echo '<div>json data 2 je: ('.$test_json_data2.')</div>';
        
        echo '<br /><div>is valid data 1 vraci:</div>';
        var_dump($validate_result1);
        
        echo '<br /><div>is valid data 2 vraci:</div>';
        var_dump($validate_result2);
        
        //echo '<br /><div>validace vraci:</div>';
        //var_dump($result);
        
        //return $this->container->view->render($response, 'full.twig');
    }
    
    public function schematest($request, $response)
    {
        $store = new SchemaStore;
        $urlBase = "http://example.com/";
        
        echo '<h1>test na vlozene schema</h1>';
        
        $schema_root = json_decode(file_get_contents('/var/www/html/glued/glued/Controllers/Api/v0_1/schemas/timepixels_root.json'));
        $schema_user = json_decode(file_get_contents('/var/www/html/glued/glued/Controllers/Api/v0_1/schemas/timepixels_user.json'));
        
        echo '<br /><div>zakladni schema s ref udajem pro users</div>';
        
        echo '<div>'.print_r(json_encode($schema_root), true).'</div>';
        
        // priradime to vymyslene adrese http://example.com/test-schema
        $store->add($urlBase . "test-schema", $schema_root);
        // dodame druhou vymyslenou adresu http://example.com/timepixels_user
        $store->add($urlBase . "timepixels_user", $schema_user);
        // vratime schema spojene s prvni adresou, s uz nahrazenym ref
        $schema	 = $store->get($urlBase . "test-schema");
        
        echo '<br /><div>s chema s doplnenym subschematem</div>';
        echo '<div>'.print_r(json_encode($schema), true).'</div>';
        
        
        // overeni pres data
        
        // nejdriv spatna data, protoze users je povinne
        $payload = '
        { "data":
          {
           "title": "New event",
           "dt_start": "2017-02-13 15:00",
           "dt_end": "2017-02-13 16:00"
          }
        }
        ';
        
        // pak spravna data (pozor na integer id)
        $payload2 = '
        { "data":
          {
           "title": "New event",
           "dt_start": "2017-02-13 15:00",
           "dt_end": "2017-02-13 16:00",
           "users": [{
            "id": 5,
            "name": "kuba"
           }]
          }
        }
        ';
        
        // validace spatnych dat
        $jsonvr = jsonv::isValid(json_decode($payload), $schema);
        echo '<br /><br /><div>chybny payload je: ('.$payload.')</div>';
        echo '<div>is valid payloadu vraci:</div>';
        var_dump($jsonvr);
        
        // validace spravnych dat
        $jsonvr2 = jsonv::isValid(json_decode($payload2), $schema);
        echo '<br /><br /><div>spravny payload2 je: ('.$payload2.')</div>';
        echo '<div>is valid payloadu2 vraci:</div>';
        var_dump($jsonvr2);
        
    }
    
    public function schematest2($request, $response)
    {
$store = new SchemaStore;
$urlBase = "http://example.com/";
    
    

// Add external $ref, and don't resolve it
// While we're at it, use an array, not an object
$schema	 = array(
	"title"		 => "Test schema 2",
	"properties" => array(
		"foo" => array('$ref' => "somewhere-else")
	)
);

$otherSchema = json_decode('{
	"title": "Somewhere else",
	"item": {
        "huuu": { "type": "string" }
    }
}');

echo '<br /><div>zakladni</div>';
var_dump($schema);

$store->add($urlBase . "test-schema-2", $schema);
$store->add($urlBase . "somewhere-else", $otherSchema);
$schema	 = $store->get($urlBase . "test-schema-2");

echo '<br /><div>finale</div>';
var_dump($schema);

/*


echo '<br /><div>chybi</div>';
var_dump($store->missing());


echo '<br /><div>mezikrok</div>';
var_dump($schema);




$store->add($urlBase . "somewhere-else", $otherSchema);
$schema	 = $store->get($urlBase . "test-schema-2");

*/

    }
}
