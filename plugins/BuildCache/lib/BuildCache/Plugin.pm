package BuildCache::Plugin;

use strict;

sub _hdlr_buildcache {
    my ( $ctx, $args, $cond ) = @_;
    my $ttl = $args->{ ttl } or return '';
    my $key = $args->{ key } or return '';
    require MT::Request;
    my $r = MT::Request->instance;
    my $cache = $r->cache( 'buildcache:' . $key );
    return $cache if $cache;
    require MT::Cache::Negotiate;
    my $driver = MT::Cache::Negotiate->new( ttl => $ttl );
    my $value = $driver->get( $key );
    if ( $value ) {
        if (! Encode::is_utf8( $value ) ) {
            Encode::_utf8_on( $value );
        }
        $r->cache( 'buildcache:' . $key, $value );
        return $value;
    } else {
        $value = $ctx->stash( 'builder' )->build( $ctx, $ctx->stash( 'tokens' ), $cond );
        $driver->replace( $key, $value, $ttl );
        $r->cache( 'buildcache:' . $key, $value );
        return $value;
    }
}

1;