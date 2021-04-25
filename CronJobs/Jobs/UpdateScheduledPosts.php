<?php
/**
 * @license http://opensource.org/licenses/MIT MIT
 */


namespace Rdb\Modules\RdbCMSA\CronJobs\Jobs;


/**
 * Update schedules posts status that reached published date/time to published.
 * 
 * @since 0.0.6
 */
class UpdateScheduledPosts
{


    /**
     * Execute the job.
     * 
     * @param \Rdb\System\Libraries\Db $Db The Database class.
     */
    public static function execute(\Rdb\System\Libraries\Db $Db)
    {
        $sql = 'UPDATE `' . $Db->tableName('posts') . '` AS `posts` 
            SET `post_status` = 1
            WHERE `posts`.`post_status` = 2 AND `posts`.`post_publish_date_gmt` <= :publish_date_gmt';
        $Sth = $Db->PDO()->prepare($sql);
        unset($sql);
        $Sth->bindValue(':publish_date_gmt', gmdate('Y-m-d H:i:s'));
        $Sth->execute();
        $Sth->closeCursor();
        unset($Sth);
    }// execute


}
