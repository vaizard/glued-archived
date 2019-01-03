<?php

// ruzne zakladni funkce, pouzivane v libovolnych modulech

namespace Glued\Classes\Core;

class Core

{
    // pro pouziti containeru ve funkcich teto tridy
    protected $container;
    
    public function __construct($container)
    {
        $this->container = $container;
    }
    
    
    // vyhodi seznam cilu pro data v json schematu v json path like tvaru
    public function json_schema_targets($schema_object, $begin = '$', $results = array())
    {
        // vime ze tam jsou nejake nody, takze prvni uroven je nazev nodu
        if (is_array($schema_object)) {
            foreach ($schema_object as $node => $dalsi_objekt) {
                $new_target = $begin.'.'.$node;
                // pokud je to objekt, ma dalsi properties
                if ($dalsi_objekt['type'] == 'object') {
                    $results = $this->container->core->json_schema_targets($dalsi_objekt['properties'], $new_target, $results);
                }
                // pokud je to array, ma items
                else if ($dalsi_objekt['type'] == 'array') {
                    // muze to byt array of objects
                    if ($dalsi_objekt['items']['type'] == 'object') {
                        $results = $this->container->core->json_schema_targets($dalsi_objekt['items']['properties'], $new_target.'.[]', $results);
                    }
                    // nebo array of array, ale to nepouzivame !!!
                    
                    // nebo primy array nejakych zakladnich prvku, ale to asi taky nepouzivame, TODO
                    
                }
                // jinak je to cilovy bod a pridame to do targetu (pouzivame boolean?)
                else if (in_array($dalsi_objekt['type'], array('integer', 'string'))) {
                    $results[] = $new_target;
                }
            }
        }
        return $results;
    }
    
}
