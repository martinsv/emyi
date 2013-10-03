<?php
/*
 * Emyi
 *
 * @link http://github.com/douggr/Emyi for the canonical source repository
 * @license http://opensource.org/licenses/MIT MIT License
 */

namespace Emyi\Auth;

/**
 * This is a complete authorization and authentication interface. You must
 * implement it to provide Auth functionalities.
 */
interface User
{
    /**
     * Authenticate a given username and password. It takes two keyword arguments,
     * username and password, and it returns a User object if the password
     * is valid for the given username. If the password is invalid, authenticate()
     * throws an Emyi\Auth\Exception.
     *
     * @param string username
     * @param string password in raw format
     * @return Emyi\Auth\User on success
     * @throws Emyi\Auth\Exception if the user could not be authenticated
     */
    public static function authenticate($username, $raw_password);

    /**
     * Returns the user instance.
     * If the user is authenticated, it'll be returned or a new user object
     * otherwise.
     *
     * @return Emyi\Auth\User
     */
    public static function getUser();

    /**
     * @param Emyi\Auth\User
     * @return Emyi\Auth\User
     */
    public static function setUser(User $user);

    /**
     * Returns true if the user has any permissions in the given package
     * If the user is inactive, this method will always return false.
     *
     * @param string The request handler
     * @param string The request method
     * @return boolean
     */
    public function hasPerm($handler, $request_method);

    /**
     * This is a way to tell if the user has been authenticated. This does
     * not imply any permissions, and doesn't check if the user is active - it
     * only indicates that the user has provided a valid username and password.
     *
     * @return boolean
     */
    public function isAuthenticated();

    /**
     * Logout the user, if authenticated.
     *
     * @return void
     */
    public function logout();
}
