<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
namespace com\sergiosgc\rbac;

class Rbac {
    protected static $singleton = null;
    protected $superAdminRole = 'super_admin';
    protected $rolePermissions = array();
    protected $userRoles = array();
    /**
     * Singleton pattern instance getter
     * @return Rbac The singleton Rbac
     */
    public static function getInstance() {/*{{{*/
        if (is_null(self::$singleton)) self::$singleton = new self();
        return self::$singleton;
    }/*}}}*/
    protected function __construct() {/*{{{*/
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.facility.available_config', array($this, 'config'));
        \ZeroMass::getInstance()->register_callback('com.sergiosgc.pbac', array($this, 'grantPermission'));
    }/*}}}*/
    public function config() {/*{{{*/
        $this->superAdminRole = \com\sergiosgc\Facility::get('config')->get('com.sergiosgc.rbac.superadmin', false, $this->superAdminRole);
        $indexes = \com\sergiosgc\Facility::get('config')->getKeys('com.sergiosgc.rbac.role');
        foreach ($indexes as $role) {
            $permissions = \com\sergiosgc\Facility::get('config')->get('com.sergiosgc.rbac.role.' . $role . '.permissions');
            $permissions = explode(',', $permissions);
            $this->rolePermissions[$role] = $permissions;
        }
    }/*}}}*/
    protected function getUserRoles($user) {/*{{{*/
        if (!isset($this->userRoles[$user])) {
            $loggedInUser = \com\sergiosgc\Facility::get('user')->getLoggedIn();

            $userRestEntity = 'user';
            /*#
             * Rbac is trying to find out a user role list. Allow the entity name for "user" to be filtered
             *
             * @param string User entity name
             * @return string User entity name
             */
            $userRestEntity = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rbac.user_entity', $userRestEntity);

            $userUsernameField = 'username';
            /*#
             * Rbac is trying to find out a user role list. Allow the username field name to be filtered
             *
             * @param string Username field name
             * @return string Username field name
             */
            $userUsernameField = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rbac.username_field', $userUsernameField);

            $userRoleField = 'role';
            /*#
             * Rbac is trying to find out a user role list. Allow the role field name to be filtered
             *
             * @param string Role field name
             * @return string Role field name
             */
            $userRoleField = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rbac.role_field', $userRoleField);

            $loggedInUser = \com\sergiosgc\Facility::get('REST')->read($userRestEntity, array($userUsernameField => $loggedInUser));
            /*#
             * Rbac just read the current logged in user from REST. Allow it to be mangled
             *
             * @param array Logged in user fields
             * @return array Logged in user fields
             */
            $loggedInUser = \ZeroMass::getInstance()->do_callback('com.sergiosgc.rbac.logged_in_user', $loggedInUser);
            if ( count($loggedInUser) != 1 || !isset($loggedInUser[0][$userRoleField])) {
                $this->userRoles[$user] = explode(',', \com\sergiosgc\Facility::get('config')->get('com.sergiosgc.rbac.user.' . $user . '.roles', false, ''));
            } else {
                if (strpos($loggedInUser[0][$userRoleField], ',')) {
                    $this->userRoles[$user] = explode(',', $loggedInUser[0][$userRoleField]);
                } else {
                    $this->userRoles[$user] = array( $loggedInUser[0][$userRoleField] );
                }
            }
        }
        return $this->userRoles[$user];
    }/*}}}*/
    protected function roleHasPermission($role, $permission) {/*{{{*/
        if ($role == $this->superAdminRole) return true;
        if (!isset($this->rolePermissions[$role])) return false;
        return in_array($permission, $this->rolePermissions[$role]);
    }/*}}}*/
    protected function userHasPermission($user, $permission) {/*{{{*/
        foreach ($this->getUserRoles($user) as $role) if ($this->roleHasPermission($role, $permission)) return true;
        return false;
    }/*}}}*/
    public function grantPermission($result, $permission) {/*{{{*/
        if ($result) return $result;
        if ($permission == '') return true;
        if (!\com\sergiosgc\Facility::get('user')->isLoggedIn()) return $result;
        if ($this->userHasPermission(\com\sergiosgc\Facility::get('user')->getLoggedIn(), $permission)) return true;
        return $result;
    }/*}}}*/
}

Rbac::getInstance();

/*#
 * Role Based Access Control
 *
 * Building on top of com.sergiosgc.user and com.sergiosgc.pbac, provides
 * role based access control
 *
 * # Usage summary 
 *
 * TBD
 *
 * @author Sérgio Carvalho <sergiosgc@gmail.com>
 * @copyright 2012, Sérgio Carvalho
 * @version 1.0
 */
?>
