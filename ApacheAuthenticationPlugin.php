<?php
/**
 * Plugin to enable Apache Authentication
 *
 * @category  Plugin
 * @package   StatusNet
 * @author    Beau Gunderson <beau@beaugunderson.com>
 */

if (!defined('STATUSNET') && !defined('LACONICA')) {
   exit(1);
}

class ApacheAuthenticationPlugin extends AuthenticationPlugin
{
   function onInitializePlugin()
   {
      parent::onInitializePlugin();

      if (!isset($this->domain))
      {
         throw new Exception("must specify a domain attribute");
      }

      $this->ldapCommon = new LdapCommon(get_object_vars($this));
   }

   function onAutoload($cls)
   {
      switch ($cls)
      {
      case 'ApacheLoginAction':
         require_once(INSTALLDIR.'/plugins/ApacheAuthentication/ApacheLogin.php');

         return false;
      case 'LdapCommon':
         require_once(INSTALLDIR.'/plugins/LdapCommon/LdapCommon.php');

         return false;
      }
   }

   function onArgsInitialize(&$args)
   {
      if ($args['action'] == 'login')
      {
         $args['action'] = 'ApacheLogin';
      }
   }

   /*
   // XXX I think this is only needed if we're offering multiple login methods?
   function onStartInitializeRouter($m)
   {
      $m->connect('main/apache_login', array('action' => 'ApacheLogin'));

      return true;
   }
   */

   function onLoginAction($action, &$login)
   {
      switch ($action)
      {
      case 'ApacheLogin':
         $login = true;

         return false;

         break;
      default:
         return true;
      }
   }

   function mungeUsername($username)
   {
      if (preg_match('/\\\\(?P<username>.*)/', $username, $matches))
      {
         $username = $matches["username"];

         common_log(LOG_DEBUG, __METHOD__ .' usernamed munged to '. $username);
      }

      return strtolower($username);
   }

   // XXX I think this relies on username == nickname
   // Had to add this so API basic authentication would work
   function onStartCheckPassword($nickname, $password, &$authenticatedUser)
   {
      common_log(LOG_DEBUG, __METHOD__ .' called with '. $nickname .', '. $password);

      $nickname = $this->mungeUsername($nickname);

      $user = User::staticGet('nickname', common_canonical_nickname($nickname));

      if (!empty($user))
      {
         if ($this->checkPassword($nickname, $password))
         {
            $authenticatedUser = $user;

            return false;
         }
      }

      return parent::onStartCheckPassword($nickname, $password, $authenticatedUser);
   }

   function checkPassword($username, $password)
   {
      $remote = $this->mungeUsername($_SERVER['REMOTE_USER']);
      $username = $this->mungeUsername($username);

      common_log(LOG_DEBUG, __METHOD__ .' checkPassword called with '. $username .', '. $password .', $remote is '. $remote);

      if ($username == $remote)
      {
         common_log(LOG_DEBUG, __METHOD__ .' authentication passed');

         return true;
      }

      common_log(LOG_DEBUG, __METHOD__ .' authentication failed');

      return false;
   }

   function autoRegister($username, $nickname)
   {
      $username = $this->mungeUsername($username);

      common_log(LOG_DEBUG, __METHOD__ .' autoRegister called with '. $username .', '. $nickname);

      if (is_null($nickname))
      {
         $nickname = $username;
      }

      $entry = $this->ldapCommon->get_user($username, $this->attributes);

      $registration_data = array();

      if($entry)
      {
         foreach($this->attributes as $sn_attribute => $ldap_attribute)
         {
            if($sn_attribute != 'password')
            {
               $registration_data[$sn_attribute] = $entry->getValue($ldap_attribute, 'single');
            }
         }

         if(!isset($registration_data['email']) || empty($registration_data['email']))
         {
            $registration_data['email'] = $username ."@". $this->domain;
         }
      }
      else
      {
         $registration_data['email'] = $username ."@". $this->domain;
      }

      $registration_data['email_confirmed'] = true;

      $registration_data['nickname'] = $nickname;
      $registration_data['password'] = common_good_rand(16);

      return User::register($registration_data);
   }

   function suggestNicknameForUsername($username)
   {
      $username = $this->mungeUsername($username);

      return $username;

      // XXX Don't call common_nicknameize since usernames
      // may contain periods and it will strip them
      //return common_nicknamize($username);
   }

   function onPluginVersion(&$versions)
   {
      $versions[] = array('name' => 'Apache Authentication',
         'version' => STATUSNET_VERSION,
         'author' => 'Beau Gunderson',
         'homepage' => 'http://status.net/wiki/Plugin:ApacheAuthentication',
         'rawdescription' =>
         _m('The Apache Authentication plugin makes StatusNet let Apache handle authentication.'));

      return true;
   }
}
