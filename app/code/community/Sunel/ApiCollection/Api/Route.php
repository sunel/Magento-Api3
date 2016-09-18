<?php

class Sunel_ApiCollection_Api_Route 
{
	public function getRoutes($api)
	{
		$api->version('v1', function ($api) {
			$api->get('stores', 'tapi/v1_store@getStoreList');
		    $api->group(['middleware' => 'store_check'], function ($api) {
		        $api->post('token', 'tapi/v1_store@getToken');
		        $api->group(['middleware' => 'tokenized'], function ($api) {
		        	$this->addStoreRoute($api, 'v1');
		        });
		    });
		});
	}

	protected function addStoreRoute($api, $version)
	{
		$api->get('country/list', "tapi/{$version}_store@getCountryList");
		$api->get('region/list', "tapi/{$version}_store@getRegionList");
		$api->get('block/{id}', "tapi/{$version}_store@getBlock");
		$api->get('cms/{id}', "tapi/{$version}_store@getCms");
	}
}