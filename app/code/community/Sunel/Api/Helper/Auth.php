<?php

class Sunel_Api_Helper_Auth extends Mage_Core_Helper_Abstract
{
 	const XML_PATH_API3_KEY = 'global/api3/key';

    public function create($data,$uid = null)
    {
        $key = (string) Mage::getConfig()->getNode(self::XML_PATH_API3_KEY);
        $signer = new \Lcobucci\JWT\Signer\Hmac\Sha256();
        $token = new \Lcobucci\JWT\Builder();

        $token->setId($key , true)
              ->setIssuedAt(time())
              ->setHeader('alg','HS256')
              ->setHeader('typ','JWT')
              ->set('payload', $data);
        if($uid){
          $token->set('uid',$uid);
        }      
        $token->sign($signer, $key);

        return $token->getToken();
    }

    public function decode($token)
    {
 		$token = $this->validate($token);

        return $token;
    }

    public function validate($token)
    {
    	$key = (string) Mage::getConfig()->getNode(self::XML_PATH_API3_KEY);
    	$parser = new \Lcobucci\JWT\Parser;
        $token = $parser->parse((string) $token);

    	$data = new \Lcobucci\JWT\ValidationData;

    	$data->setId($key);
    	$signer = new \Lcobucci\JWT\Signer\Hmac\Sha256();

    	if(!$token->validate($data) || !$token->verify($signer, $key)) {
    		throw new \Sunel\Api\Exception\TokenInvalidException('Invalid Token.');
    	}
    	return $token;
    }

    public function byId($userId)
    {
        $user = Mage::getModel('customer/customer')->load($userId);

        if(!$user->getId()){
            throw new \Sunel\Api\Exception\TokenInvalidException('Invalid Token.');
        }
        
        return $user;
    }

}