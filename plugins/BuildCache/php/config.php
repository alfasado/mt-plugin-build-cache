<?php
class BuildCache extends MTPlugin {
    var $app;
    var $registry = array(
        'name' => 'BuildCache',
        'id'   => 'BuildCache',
        'key'  => 'buildcache',
        'tags' => array(
            'block' => array( 'buildcache' => '_hdlr_buildcache' ),
        ),
    );

    function _hdlr_buildcache ( $args, $content, &$ctx, &$repeat ) {
        $app = $ctx->stash( 'bootstrapper' );
        $ttl = $args[ 'ttl' ];
        $key = $args[ 'key' ];
        $ttl = $app->escape( $ttl );
        $key = $app->escape( $key );
        $cache = $ctx->stash( 'buildcache:' . $key );
        if ( isset( $cache ) ) {
            return $cache;
        }
        require_once( 'class.mt_session.php' );
        $session = new Session;
        $duration = time();
        $where = "session_id = '{$key}' AND session_duration > '{$duration}'";
        $extra = array( 'limit' => 1 );
        $cache = $session->Find( $where, FALSE, FALSE, $extra );
        if ( isset( $cache ) ) {
            $ctx->stash( 'buildcache:' . $key, $cache[ 0 ]->data );
            return $cache[ 0 ]->data;
        } else {
            if ( isset( $content ) ) {
                $session->session_id = $key;
                $session->session_kind = 'CO';
                $session->session_start = $duration;
                $session->session_duration = $duration + $ttl;
                $session->data = $content;
                $session->Save();
                $repeat = FALSE;
                $ctx->stash( 'buildcache:' . $key, $content );
                return $content;
            }
        }
    }
}

?>