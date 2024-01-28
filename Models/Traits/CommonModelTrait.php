<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\Models\Traits;


/**
 * Common model's trait.
 * 
 * @since 0.0.14
 */
trait CommonModelTrait
{


    /**
     * Common model trait get cached result of common list items method between models.
     * 
     * @param array $cacheArgs The requested method arguments. Use `func_get_args()` to get them.
     * @param mixed $cacheResult The default value of cached result. Recommend set this using `null`.
     * @return mixed Return cached result or same result as `$cacheResult`.
     */
    protected function cmtGetCacheListItems(array $cacheArgs, $cacheResult = null)
    {
        if (!$this->Container->has('Plugins')) {
            return $cacheResult;
        }

        /* @var $Plugins \Rdb\Modules\RdbAdmin\Libraries\Plugins */
        $Plugins = $this->Container->get('Plugins');

        /*
         * PluginHook: Rdb\Modules\RdbCMSA\Models\->listItemsGetCache
         * PluginHookDescription: Hook to get cache on the common hook name but different model class names, methods, arguments.
         * PluginHookParam: <br>
         *              null|array $cacheResult The cache result. Begin from `null` and if found cached in the plugins then it should return as array.<br>
         *              string $class This model class name.<br>
         *              string $function This class's method name (function name only).<br>
         *              array $args The method's arguments that was called.<br>
         * PluginHookSince: 0.0.14
         */
        $cacheResult = $Plugins->doAlter(
            'Rdb\Modules\RdbCMSA\Models\->listItemsGetCache', 
            $cacheResult,
            get_called_class(),
            debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'],
            $cacheArgs
        );

        unset($Plugins);
        return $cacheResult;
    }// cmtGetCacheListItems


    /**
     * Common model trait set cached result of common list items method between models.
     * @param array $cacheArgs The requested method arguments. Use `func_get_args()` to get them.
     * @param array $output The output result from common list items method in the models.
     */
    protected function cmtSetCacheListItems(array $cacheArgs, array $output)
    {
        if (!$this->Container->has('Plugins')) {
            return $output;
        }

        /* @var $Plugins \Rdb\Modules\RdbAdmin\Libraries\Plugins */
        $Plugins = $this->Container->get('Plugins');

        /*
         * PluginHook: Rdb\Modules\RdbCMSA\Models\->listItemsSetCache
         * PluginHookDescription: Hook to get cache on the common hook name but different model class names, methods, arguments.
         * PluginHookParam: <br>
         *              array $output The output result.<br>
         *              string $class This model class name.<br>
         *              string $function This class's method name (function name only).<br>
         *              array $args The method's arguments that was called.<br>
         * PluginHookReturn: None.
         * PluginHookSince: 0.0.14
         */
        $Plugins->doHook(
            'Rdb\Modules\RdbCMSA\Models\->listItemsSetCache', 
            [
                $output,
                get_called_class(),
                debug_backtrace(!DEBUG_BACKTRACE_PROVIDE_OBJECT|DEBUG_BACKTRACE_IGNORE_ARGS, 2)[1]['function'],
                $cacheArgs,
            ]
        );

        unset($Plugins);
    }// cmtSetCacheListItems


}
