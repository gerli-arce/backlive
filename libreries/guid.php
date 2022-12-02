<?php

/**
 * guid es una clase que genera un id random.
 * 
 * Propiedad de SoDe World
 */
class guid
{
    /**
     * Función que genera un id corto
     * @return string - una cadena de 8 caracteres.
     */
    static public function short(): string
    {
        $id = uniqid();
        $uid = hash('CRC32', $id);
        return $uid;
    }

    /**
     * Función que genera un id largo
     * @return string - una cadena de 36 caracteres.
     */
    static public function long(): string
    {
        $id = uniqid();
        $uid = hash('md5', $id);

        $c1 = substr($uid, 0, 8);
        $c2 = substr($uid, 8, 4);
        $c3 = substr($uid, 12, 4);
        $c4 = substr($uid, 16, 4);
        $c5 = substr($uid, 20);

        return "{$c1}-{$c2}-{$c3}-{$c4}-{$c5}";
    }
}
