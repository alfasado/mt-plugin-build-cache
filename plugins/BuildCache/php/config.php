<?php
class BuildCache extends MTPlugin {
    var $app;
    var $registry = array(
        'name' => 'BuildCache',
        'id'   => 'BuildCache',
        'key'  => 'buildcache',
        'tags' => array(
            'block' => array( 'buildcache' => '_hdlr_buildcache',
                              'cachecontent' => '_hdlr_cache_content',
                              'ifcachedcontent' => '_hdlr_if_cached_content' ),
            'function' => array( 'cachedcontent' => '_hdlr_cached_content',
                                 'cachedcdata' => '_hdlr_cached_content' ),
        ),
    );

    function _hdlr_cached_content ( $args, &$ctx ) {
        $app = $ctx->stash( 'bootstrapper' );
        $key = $args[ 'key' ];
        $key = $app->escape( $key );
        $cache = $ctx->stash( 'buildcache:' . $key );
        return $cache;
    }

    function _hdlr_cache_content ( $args, $content, &$ctx, &$repeat ) {
        if ( isset( $content ) ) {
            $app = $ctx->stash( 'bootstrapper' );
            $ttl = $args[ 'ttl' ];
            $key = $args[ 'key' ];
            $ttl = $app->escape( $ttl );
            $key = $app->escape( $key );
            $session = $app->get_by_key( 'Session', array( 'id' => $key, 'kind' => 'CO' ) );
            $ts = time();
            $session->session_start = $ts;
            $session->session_duration = $ts + $ttl;
            $session->data = $content;
            $session->Save();
        }
        return $content;
    }

    function _hdlr_if_cached_content ( $args, $content, &$ctx, &$repeat ) {
        if (! isset( $content ) ) {
            $app = $ctx->stash( 'bootstrapper' );
            $args[ 'check_cache' ] = 1;
            $ttl = $args[ 'ttl' ];
            $key = $args[ 'key' ];
            $ttl = $app->escape( $ttl );
            $key = $app->escape( $key );
            $session = $app->load( 'Session', array( 'id' => $key, 'kind' => 'CO' ), array( 'limit' => 1 ) );
            if ( $session ) {
                $limit = time() + $ttl;
                if ( $session->start < $limit ) {
                    $ctx->stash( 'buildcache:' . $key, $session->data );
                    return $ctx->_hdlr_if( $args, $content, $ctx, $repeat, TRUE );
                } else {
                    $session->Remove();
                }
            }
            return $ctx->_hdlr_if( $args, $content, $ctx, $repeat, FALSE );
        } else {
            return $ctx->_hdlr_if( $args, $content, $ctx, $repeat );
        }
    }

    function _hdlr_buildcache ( $args, $content, &$ctx, &$repeat ) {
        $app = $ctx->stash( 'bootstrapper' );
        $ttl = $args[ 'ttl' ];
        $key = $args[ 'key' ];
        $ttl = $app->escape( $ttl );
        $key = $app->escape( $key );
        if (! isset( $content ) ) {
            $session = $app->load( 'Session', array( 'id' => $key, 'kind' => 'CO' ) );
            if ( $session ) {
                $session = $session[ 0 ];
                $limit = time() + $ttl;
                if ( $session->start < $limit ) {
                    $ctx->stash( 'buildcache:' . $key, $session->data );
                    $repeat = FALSE;
                    echo $session->session_data;
                    return;
                } else {
                    $session->Remove();
                }
            }
        }
        if ( isset( $content ) ) {
            $session = $app->get_by_key( 'Session', array( 'id' => $key, 'kind' => 'CO' ) );
            $ts = time();
            $session->session_start = $ts;
            $session->session_duration = $ts + $ttl;
            $session->data = $content;
            $session->Save();
        }
        return $content;
    }
}

?>