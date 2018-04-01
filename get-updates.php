<?php

class notify_get_updates
{
    public function match_request($request)
	{
		return qa_opt('qa_notify_enabled') && $request == 'notify-get-updates';
    }
    
    public function process_request($request)
    {
        $userid = qa_get_logged_in_userid();
        $since = qa_get('since');

        $response = array(
            'user' => $this->get_user_details($userid),
            'feed' => $this->get_feed($userid, $since)
        );

        header('Content-Type: application/json');

        echo json_encode($response);

        return null;
    }

    private function get_user_details($userid)
    {
        return array(
            'name' => qa_get_logged_in_handle(),
            'points' => intval(qa_get_logged_in_points()),
            'badges' => $this->get_badge_details($userid)
        );
    }

    private function get_badge_details($userid)
    {
        $badge_response = qa_db_read_all_values(
            qa_db_query_sub(
                'SELECT badge_slug FROM ^userbadges WHERE user_id=#',
                $userid
            )
        );

        $badges = qa_get_badge_list();
        $bcount = array();
        $badge_counts = array();

        foreach ($badge_response as $slug) {
            $bcount[$badges[$slug]['type']] = isset($bcount[$badges[$slug]['type']])?$bcount[$badges[$slug]['type']]+1:1; 
        }

        for ($i = 0; $i < 3; $i++) {
            $type = qa_get_badge_type($i);
            $badge_counts[$type['slug']] = isset($bcount[$i]) ? $bcount[$i] : 0;
        }

        return $badge_counts;
    }

    private function get_feed($userid, $since)
    {
        $filter = '(nf.userid IS NULL OR nf.userid = #)';
        if ($since) {
            $filter .= ' AND datetime > $';
        }

        $sql =
            'SELECT datetime, uu.handle as user, pu.handle as poster, kind, title, message, parentid, postid ' .
            'FROM ^notifyfeed nf ' .
            'LEFT JOIN ^users uu ON nf.userid = uu.userid ' .
            'LEFT JOIN ^users pu ON nf.posterid = pu.userid ' .
            'WHERE ' . $filter . ' ' .
            'ORDER BY datetime DESC';
    
        if ($since) {
            $query = qa_db_query_sub($sql, $userid, $since);
        } else {
            $query = qa_db_query_sub($sql, $userid);
        }

        return qa_db_read_all_assoc($query);
    }
}
