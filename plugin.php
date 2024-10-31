<?php
/*
 * Plugin Name: Polaris Flexible SSL
 * Description: Fix For Polaris Flexible SSL
 * Version: 1.0.1
 * Text Domain: polaris-flexible-ssl
 * Author: Polaris Dev
 */

class Flexible_SSL
{

    public function __construct()
    {
    }

    public function run()
    {
        if (!$this->isSsl() && $this->isSslToNonSslProxy()) {
            $_SERVER['HTTPS'] = 'on';
            add_action('shutdown', array($this, 'maintainPluginLoadPosition'));
        }
    }

    /**
     * @return bool
     */
    private function isSsl()
    {
        return function_exists('is_ssl') && is_ssl();
    }

    /**
     * @return bool
     */
    private function isSslToNonSslProxy()
    {
        $isProxy = false;

        $serverKeys = array('HTTP_X_POLARIS_FROM', 'HTTP_X_POLARIS_VISITOR_COUNTRY');
        foreach ($serverKeys as $sKey) {
            if (isset($_SERVER[$sKey])) {
                $isProxy = true;
                break;
            }
        }

        return $isProxy;
    }

    /**
     * Sets this plugin to be the first loaded of all the plugins.
     */
    public function maintainPluginLoadPosition()
    {
        $sBaseFile = plugin_basename(__FILE__);
        $nLoadPosition = $this->getActivePluginLoadPosition($sBaseFile);
        if ($nLoadPosition > 1) {
            $this->setActivePluginLoadPosition($sBaseFile, 0);
        }
    }

    /**
     * @param string $sPluginFile
     * @return int
     */
    private function getActivePluginLoadPosition($sPluginFile)
    {
        $sOptionKey = is_multisite() ? 'active_sitewide_plugins' : 'active_plugins';
        $aActive = get_option($sOptionKey);
        $nPosition = -1;
        if (is_array($aActive)) {
            $nPosition = array_search($sPluginFile, $aActive);
            if ($nPosition === false) {
                $nPosition = -1;
            }
        }
        return $nPosition;
    }

    /**
     * @param string $sPluginFile
     * @param int    $nDesiredPosition
     */
    private function setActivePluginLoadPosition($sPluginFile, $nDesiredPosition = 0)
    {

        $aActive = $this->setArrayValueToPosition(get_option('active_plugins'), $sPluginFile, $nDesiredPosition);
        update_option('active_plugins', $aActive);

        if (is_multisite()) {
            $aActive = $this->setArrayValueToPosition(get_option('active_sitewide_plugins'), $sPluginFile, $nDesiredPosition);
            update_option('active_sitewide_plugins', $aActive);
        }
    }

    /**
     * @param array $aSubjectArray
     * @param mixed $mValue
     * @param int   $nDesiredPosition
     * @return array
     */
    private function setArrayValueToPosition($aSubjectArray, $mValue, $nDesiredPosition)
    {

        if ($nDesiredPosition < 0 || !is_array($aSubjectArray)) {
            return $aSubjectArray;
        }

        $nMaxPossiblePosition = count($aSubjectArray) - 1;
        if ($nDesiredPosition > $nMaxPossiblePosition) {
            $nDesiredPosition = $nMaxPossiblePosition;
        }

        $nPosition = array_search($mValue, $aSubjectArray);
        if ($nPosition !== false && $nPosition != $nDesiredPosition) {

            unset($aSubjectArray[$nPosition]);
            $aSubjectArray = array_values($aSubjectArray);

            array_splice($aSubjectArray, $nDesiredPosition, 0, $mValue);
        }

        return $aSubjectArray;
    }
}

$polarisFlexibleSsl = new Flexible_SSL();
$polarisFlexibleSsl->run();
