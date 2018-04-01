<?php

class notify_event_logger
{
	public function init_queries($table_list)
	{
        $tablename = qa_db_add_table_prefix('notifyfeed');

        if (!in_array($tablename, $table_list)) {
            // table does not exist, so create it
            require_once QA_INCLUDE_DIR . 'app/users.php';
            require_once QA_INCLUDE_DIR . 'db/maxima.php';

            return 'CREATE TABLE ^notifyfeed (' .
                'notifyfeedid INT NOT NULL AUTO_INCREMENT,' .
                'datetime DATETIME NOT NULL,' .
                'userid ' . qa_get_mysql_user_column_type() . ' NULL,' .
                'posterid ' . qa_get_mysql_user_column_type() . ' NULL,' .
                'kind VARCHAR(40) NOT NULL,' .
                'title VARCHAR(100) NOT NULL,' .
                'message VARCHAR(500) NULL,' .
                'parentid INT NULL,' .
                'postid INT NULL,' .
                'PRIMARY KEY (notifyfeedid)' .
                ') ENGINE=MyISAM DEFAULT CHARSET=utf8';
		}

		return array();
    }

    public function admin_form(&$qa_content)
	{
		// Process form input

		$saved = false;

		if (qa_clicked('qa_notify_save_button')) {
			qa_opt('qa_notify_enabled', !!qa_post_text('qa_notify_enabled_field'));

			$saved = true;
        }
        
        return array(
			'ok' => ($saved && !isset($error)) ? 'Plugin settings saved' : null,

			'fields' => array(
                array(
					'label' => 'Activate Plugin',
					'tags' => 'name="qa_notify_enabled_field" id="qa_notify_enabled_field"',
					'value' => qa_opt('qa_notify_enabled'),
					'type' => 'checkbox',
                )
            ),
                
            'buttons' => array(
                array(
                    'label' => 'Save Changes',
                    'tags' => 'name="qa_notify_save_button"',
                ),
            )
        );
    }

    public function process_event($event, $userid, $handle, $cookieid, $params)
    {
        if (!qa_opt('qa_notify_enabled')) {
            return;
        }
        
        switch ($event) {
            case 'q_post':
            case 'a_post':
            case 'c_post':
            case 'a_select':
                $parentid = $params['parentid'];
                $postid = $params['postid'];
                $message = $params['text'];
                $root = $this->get_root_post_data($parentid);
                $rootid = $root['postid'];
                $title = $root['title'];

                if ($parentid) {
                    $userids = $this->get_all_user_ids($postid, $parentid);
        
                    foreach ($userids as $id) {
                        if ($id != $userid) {
                            $this->create_notify_feed($id, $userid, $event, $title, $message, $rootid, $postid);
                        }
                    }
                } else {
                    $this->create_notify_feed(null, $userid, $event, $title, $message, $rootid, $postid);
                }
                break;

            case 'badge_awarded':
                $postid = isset($params['postid']) ? $params['postid'] : null;
                if (isset($params['parentid'])) {
                    $root = $this->get_root_post_data($params['parentid']);
                    $rootid = $root['postid'];
                } else {
                    $rootid = null;
                }

                $title = 'Awarded \'' . qa_opt('badge_'.$params['badge_slug'].'_name') . '\' badge';
                
                $this->create_notify_feed($userid, null, $event, $title, null, $rootid, $postid);
                break;
        }
    }

    private function get_all_user_ids($postid, $parentid)
    {
        $post = $this->get_post_data($postid);
        $ids = array();

        // The user ID returned is:
        //
        //   * Sibling posts of the same type;
        //   * All parent posts.
        //
        
        $siblings = qa_db_read_all_values(
            qa_db_query_sub(
                'SELECT userid FROM ^posts WHERE parentid=# AND type=$',
                $parentid, $post['type']
            )
        );

        foreach ($siblings as $sibling) {
            $ids[intval($sibling)] = true;
        }

        while ($parentid) {
            $parent = $this->get_post_data($parentid);
            $parentid = $parent['parentid'];
            $ids[$parent['userid']] = true;
        }

        return array_keys($ids);
    }

    private function create_notify_feed($userid, $posterid, $kind, $title, $message, $parentid, $postid)
    {
        qa_db_query_sub(
            'INSERT INTO ^notifyfeed (datetime, userid, posterid, kind, title, message, parentid, postid) '.
            'VALUES (NOW(), #, #, $, $, $, #, #)',
            $userid, $posterid, $kind, $title, $message, $parentid, $postid
        );
    }
		
    private function get_post_data($id)
    {
        return qa_db_read_one_assoc(
            qa_db_query_sub(
                'SELECT * FROM ^posts WHERE postid=#',
                $id
            ),
            true
        );
    }

    private function get_root_post_data($id)
    {
        $post = $this->get_post_data($id);
        if ($post['parentid']) {
            return $this->get_root_post_data($post['parentid']);
        }

        return $post;
    }
}
