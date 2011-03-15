<?php
if (!defined('STATUSNET') && !defined('LACONICA')) { exit(1); }

class ApacheLoginAction extends Action
{
   function handle($args)
   {
      parent::handle($args);

      if (common_is_real_login())
      {
         $this->clientError(_m('Already logged in.'));
      }
      else
      {
         $username = $_SERVER['REMOTE_USER'];

         if (preg_match('/\\\\(?P<username>.*)/', $username, $matches))
         {
            $username = $matches["username"];
         }

         common_log(LOG_DEBUG, "$username = ". $username);

         $user = common_check_user($username, common_good_rand(16));

         if (!$user)
         {
            $this->serverError(_m('Error verifying user '. $username));

            return;
         }

         if (!common_set_user($user))
         {
            $this->serverError(_m('Error setting user.'));

            return;
         }

         common_real_login(true);

         $url = common_get_returnto();

         if ($url)
         {
            common_set_returnto(null);
         }
         else
         {
            if (common_config('site', 'private'))
            {
               $url = common_local_url('public');
            }
            else
            {
               $url = common_local_url('public', array('nickname' => $user->nickname));
            }
         }

         common_redirect($url, 303);
      }
   }
}
