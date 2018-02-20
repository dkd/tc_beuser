<?php
namespace dkd\TcBeuser\Form\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;

class BackendExcludeTableDisable extends BackendExcludeTableEnable implements FormDataProviderInterface
{
    /**
     * Add form data to result array
     *
     * @param array $result Initialized result array
     * @return array Result filled with more data
     */
    public function addData(array $result)
    {
        if ($result['tableName'] !== 'be_groups') {
            return $result;
        }

        if (static::$tcaBackup !== null) {
            foreach (static::$tcaBackup as $table => $configuration) {
                $GLOBALS['TCA'][$table] = $configuration;
            }
            static::$tcaBackup = null;
        }

        return $result;
    }

}
