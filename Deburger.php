<?php

class Deburger {
    const TYPE_ARRAY = 'array';
    const TYPE_OBJECT = 'object';
    private static  function parse($object, &$output = []){
        $orc = new ReflectionClass($object);
        foreach($orc->getProperties(ReflectionProperty::IS_PRIVATE) as $property){
            self::parseProperty($output, $object, $property, '-');
        }
        foreach($orc->getProperties(ReflectionProperty::IS_PROTECTED) as $property){
            self::parseProperty($output, $object, $property, '*');
        }
        foreach($orc->getProperties(ReflectionProperty::IS_PUBLIC) as $property){
            self::parseProperty($output, $object, $property, '-');
        }
        foreach($orc->getProperties(ReflectionProperty::IS_STATIC) as $property){
            self::parseProperty($output, $object, $property, '::');
        }
        return $output;
    }
    private static function parseProperty(&$output, $object, $property, $prefix = ''){
        $property->setAccessible(true);
        $value = $property->getValue($object);
        $type = is_object($value) ? self::TYPE_OBJECT : get_debug_type($value);
        switch($type){
            case self::TYPE_OBJECT:
                $output[$prefix.$property->getName()] = self::parse($property->getValue($object));
                break;
            default:
                $output[$prefix.$property->getName()] = $property->getValue($object);
                break;
        }
    }
    private static function walk($var){
        $output = [];
        if(is_object($var)){
            return self::parse($var, $output);
        }
        if(is_array($var)){
            foreach ($var as $key => $value){
                $output[$key] = self::walk($value);
            }
            return $output;
        }
        return $var;

    }

    public static function dump($var){
        $ch = curl_init();

        try{
            // set url
            curl_setopt($ch, CURLOPT_URL, "localhost:8090/api/log");

            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/JSON'
            ));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
                'action' => 'log',
                'data' => [(array)self::walk($var)],
                'group' => ['name' => 'debug']
            ]));

            $output = curl_exec($ch);

        }catch (\Throwable $th) {
            throw $th;
        } finally {
            curl_close($ch);
        }
    }
    public static function register(){
        // create curl resource
        $ch = curl_init();
        try{

            // set url
            curl_setopt($ch, CURLOPT_URL, "localhost:8090/api/register");

            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/JSON'
            ));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

            curl_setopt($ch, CURLOPT_HEADER, 1);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(['action' => 'register', 'project'=> 'a', 'name' => 'Deburger']));

            // $output contains the output string
            $output = curl_exec($ch);

        }catch (\Throwable $th) {
            throw $th;
        } finally {
            curl_close($ch);
        }
    }
}

function deburger($var){
    Deburger::dump($var);
}
