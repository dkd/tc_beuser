<?php
namespace dkd\TcBeuser\Form\FormDataProvider;

use TYPO3\CMS\Backend\Form\FormDataProviderInterface;

class BackendExcludeTableEnable implements FormDataProviderInterface
{
    /**
     * @var array
     */
    protected static $tcaBackup;

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

        if (static::$tcaBackup === null) {
            static::$tcaBackup = [
                'be_groups' => $GLOBALS['TCA']['be_groups'],
                'be_users' => $GLOBALS['TCA']['be_users'],
            ];
        }

        foreach (static::$tcaBackup as $table => $_) {
            $GLOBALS['TCA'][$table]['ctrl']['rootLevel'] = 0;
            foreach ($GLOBALS['TCA'][$table]['columns'] as &$fieldConfig) {
                $fieldConfig['exclude'] = 1;
            }
        }

        return $result;
    }

}
