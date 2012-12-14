<?php defined('SYSPATH') or die('No direct script access.');

class Request extends Kohana_Request {

   public function is_mobile() 
   {
   
   }
   
   public function is_tablet() 
   {
   
   }   

   public function is_web() 
   {
      return TRUE;
   }
}
