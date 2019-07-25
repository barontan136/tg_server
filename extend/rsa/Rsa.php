<?php
namespace rsa;
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
class Rsa {

    const PUB = '-----BEGIN PUBLIC KEY-----  
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC3//sR2tXw0wrC2DySx8vNGlqt  
3Y7ldU9+LBLI6e1KS5lfc5jlTGF7KBTSkCHBM3ouEHWqp1ZJ85iJe59aF5gIB2kl  
Bd6h4wrbbHA2XE1sq21ykja/Gqx7/IRia3zQfxGv/qEkyGOx+XALVoOlZqDwh76o  
2n1vP1D+tD3amHsK7QIDAQAB  
-----END PUBLIC KEY-----';
    const PRI = '-----BEGIN RSA PRIVATE KEY-----  
MIICXQIBAAKBgQC3//sR2tXw0wrC2DySx8vNGlqt3Y7ldU9+LBLI6e1KS5lfc5jl  
TGF7KBTSkCHBM3ouEHWqp1ZJ85iJe59aF5gIB2klBd6h4wrbbHA2XE1sq21ykja/  
Gqx7/IRia3zQfxGv/qEkyGOx+XALVoOlZqDwh76o2n1vP1D+tD3amHsK7QIDAQAB  
AoGBAKH14bMitESqD4PYwODWmy7rrrvyFPEnJJTECLjvKB7IkrVxVDkp1XiJnGKH  
2h5syHQ5qslPSGYJ1M/XkDnGINwaLVHVD3BoKKgKg1bZn7ao5pXT+herqxaVwWs6  
ga63yVSIC8jcODxiuvxJnUMQRLaqoF6aUb/2VWc2T5MDmxLhAkEA3pwGpvXgLiWL  
3h7QLYZLrLrbFRuRN4CYl4UYaAKokkAvZly04Glle8ycgOc2DzL4eiL4l/+x/gaq  
deJU/cHLRQJBANOZY0mEoVkwhU4bScSdnfM6usQowYBEwHYYh/OTv1a3SqcCE1f+  
qbAclCqeNiHajCcDmgYJ53LfIgyv0wCS54kCQAXaPkaHclRkQlAdqUV5IWYyJ25f  
oiq+Y8SgCCs73qixrU1YpJy9yKA/meG9smsl4Oh9IOIGI+zUygh9YdSmEq0CQQC2  
4G3IP2G3lNDRdZIm5NZ7PfnmyRabxk/UgVUWdk47IwTZHFkdhxKfC8QepUhBsAHL  
QjifGXY4eJKUBm3FpDGJAkAFwUxYssiJjvrHwnHFbg0rFkvvY63OSmnRxiL4X6EY  
yI9lblCsyfpl25l7l5zmJrAHn45zAiOoBrWqpM5edu7c  
-----END RSA PRIVATE KEY-----';
    private $pub_key;
    private $pri_key;
    
    public static function setPubKey(){
        return openssl_pkey_get_public(self::PUB);
    }
    
    public static function setPriKey(){
        return openssl_pkey_get_private(self::PRI);
    }
    //私钥加密
    public static function encryptByPriKey($data){
        openssl_private_encrypt($data,$encrypt,  self::setPriKey());//私钥加密  
        $encrypt = base64_encode($encrypt);//加密后的内容通常含有特殊字符，需要编码转换下，在网络间通过url传输时要注意base64编码是否是url安全的 
        return $encrypt;
    }
    //公钥解密
    public static function decryptByPubKey($encrypt){
        openssl_public_decrypt(base64_decode($encrypt),$decrypt,  self::setPubKey());//私钥加密的内容通过公钥可用解密出来 
        return $decrypt;
    }
    
    //公钥加密
    public static function encryptByPubKey($data){
        openssl_public_encrypt($data,$encrypt,self::setPubKey());//公钥加密  
        $encrypt = base64_encode($encrypt); 
        return $encrypt;
    }
    //私钥解密
    public static function decryptByPriKey($encrypt){
            $decrypted = '';  
            $pi_key = openssl_pkey_get_private(self::setPriKey());  
            $plainData = str_split(base64_decode($encrypt), 128);    
            foreach($plainData as $chunk){  
                $str = '';  
                $decryptionOk = openssl_private_decrypt($chunk,$str,$pi_key);//私钥解密  
                if($decryptionOk === false){  
                    return false;  
                }  
                $decrypted .= $str;  
            }  
            return $decrypted;  
    }
}
