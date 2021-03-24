<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Plugins\ChangeURLs;


/**
 * Change URLs hooks.
 */
class ChangeURLs implements \Rdb\Modules\RdbAdmin\Interfaces\Plugins
{


    /**
     * @var \Rdb\System\Container
     */
    protected $Container;


    /**
     * {@inheritDoc}
     */
    public function __construct(\Rdb\System\Container $Container)
    {
        $this->Container = $Container;
    }// __construct


    /**
     * {@inheritDoc}
     */
    public function disable()
    {
    }// disable


    /**
     * {@inheritDoc}
     */
    public function enable()
    {
    }// enable


    /**
     * {@inheritDoc}
     */
    public function registerHooks()
    {
        if ($this->Container->has('Plugins')) {
            /* @var $Plugins \Rdb\Modules\RdbAdmin\Libraries\Plugins */
            $Plugins = $this->Container->get('Plugins');
            $AdminPostsURLs = new AdminPostsURLs($this->Container);
            $Plugins->addHook('Rdb\Modules\Languages\Controllers\LanguagesController->updateAction.afterGetRedirectUrl', [$AdminPostsURLs, 'detectAdminURLs'], 10);
            unset($AdminPostsURLs, $Plugins);
        }
    }// registerHooks


    /**
     * {@inheritDoc}
     */
    public function uninstall()
    {
    }// uninstall


}
