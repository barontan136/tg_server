<?php
namespace aes;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Rsa
 *
 * @author Administrator
 */
class Aes {

    const KEY = 'mWTt86QEjri3miYw56HdzMySG8CRbfja';
    const IV = 'tBM75DyYTcr73zJG';
    
    //加密
    public static function encrypt($text=''){
        return openssl_encrypt($text,'AES-256-CBC',  self::KEY,FALSE,  self::IV);
    }
    //解密
     public static function decrypt($encrypt=''){
        return openssl_decrypt($encrypt,'AES-256-CBC',self::KEY,FALSE,  self::IV);
    }
}
