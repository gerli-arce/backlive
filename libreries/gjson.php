<?php

/**
 * gJSON es una clase que contiene métodos estáticos que son contenedores
 * para los métodos json_decode y json_encode.
 * 
 * Propiedad de SoDe World
 */
class gJSON
{
    static public function parse(string $text): array
    {
        $array = json_decode($text, true);
        return $array;
    }

    static public function stringify(mixed $object): string
    {
        $string = json_encode($object);
        return $string;
    }

    /**
     * Método que verifica si un string es un JSON válido.
     * @param text - Texto a verificar.
     * @return bool - valor booleano.
     */
    static public function parseable(string $text): bool
    {
        try {
            gJSON::parse($text);
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    /**
     * Recibe un objeto y retorna un nuevo objeto con todas las claves aplanadas.
     * @param object - El objeto que va a ser aplanado.
     * @param [prev] - La clave previa.
     * @return array un objeto con las claves y valores del objeto original, pero con las claves aplanadas.
     */
    static public function flatten(mixed $object, string $prev = ''): array
    {
        $flattened = array();
        foreach ($object as $key => $value) {
            $type = gettype($value);
            if ($type == 'array') {
                $prev_key = $prev ? "$prev.$key" : $key;
                $object2 = gJSON::flatten($value, $prev_key);
                foreach ($object2 as $key2 => $value2) {
                    $flattened[$key2] = $value2;
                }
            } else {
                $prev_key = $prev ? "$prev." : '';
                $flattened["$prev_key$key"] = $value;
            }
        }
        return $flattened;
    }
}
