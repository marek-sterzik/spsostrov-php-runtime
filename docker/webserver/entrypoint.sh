#!/bin/bash

set -e

gencmd() {
cmd_escaped="'`echo -n "$1" | sed "s/'/'\\\\\\''/g"`'"
cat <<END
#!/bin/bash
#worker command
cd /app > /dev/null 2>&1
cmd="\`realpath $cmd_escaped\`"
cd - > /dev/null 2>&1
x "\$cmd" "\$@"
END
}

echo "Starting container..." 1>&2

export ENTRYPOINT=1

dashost-install

source bootenv

grep -Rl '^#worker command$' /usr/local/bin/ | xargs rm -f

echo "generating worker commands..." 1>&2
for cmd in "${WORKER_COMMANDS[@]}"; do
    echo "  $cmd" 1>&2
    IFS=: read name path <<< "$cmd"
    gencmd "$path" > "/usr/local/bin/$name"
    chmod +x "/usr/local/bin/$name"
done

if [ -n "$PERSISTENT_DIR" ]; then
    echo "setting up persistent dir..." 1>&2
    mkdir -p "$PERSISTENT_DIR"
    chown -R "$SERVER_UID:$SERVER_GID" "$PERSISTENT_DIR"
    echo "persistent dir was set up" 1>&2
fi

cd /app > /dev/null 2>&1
echo "setting up writable files..." 1>&2
for file in "${WRITABLE_FILES[@]}"; do
    echo "  $file" 1>&2
    real_file="`realpath "$file"`"
    if echo "$real_file" | grep -q '^/app/'; then
        if ! [ -f "$file" -o -d "$file" ]; then
            if echo "$file" | grep -q '/$'; then
                mkdir "$file"
            else
                touch "$file"
            fi
        fi
        if [ -d "$file" ]; then
            chown -R "$SERVER_UID:$SERVER_GID" "$file"
        elif [ -f "$file" ]; then
            chown "$SERVER_UID:$SERVER_GID" "$file"
        fi
    fi
done
echo "writable files was set up" 1>&2
cd - > /dev/null 2>&1


# setup xdebug
if [ "$XDEBUG" = 1 ]; then
    echo "Warning: Xdebug enabled!"
    envwrite -o /etc/php/$PHPVER/mods-available/xdebug.ini /etc/php/$PHPVER/mods-available/xdebug.ini.template
    phpenmod -v ALL -s ALL xdebug
else
    phpdismod -v ALL -s ALL xdebug
fi

WORKER_ROOT="/app/$WORKER_ROOT"

export MAX_BODY_SIZE_STATEMENT="";
export MAX_BODY_SIZE_PHP_STATEMENT=""
export MEMORY_LIMIT_STATEMENT=""
if [ -n "$MAX_BODY_SIZE" ]; then
    MAX_BODY_SIZE_STATEMENT="client_max_body_size $MAX_BODY_SIZE;"
    MAX_BODY_SIZE_PHP_STATEMENT="php_admin_value[upload_max_filesize] = ${MAX_BODY_SIZE^^}"
fi
if [ -n "$MEMORY_LIMIT" ]; then
    MEMORY_LIMIT_STATEMENT="php_admin_value[memory_limit] = $MEMORY_LIMIT"
fi

export RSYSLOG_CONF=""
if [ -n "$NGINX_RSYSLOG_URL" ]; then
    RSYSLOG_CONF="access_log $NGINX_RSYSLOG_URL_NGINX;"
fi
export RSYSLOG_CONF_ERR=""
if [ -n "$NGINX_RSYSLOG_URL_ERR" ]; then
    RSYSLOG_CONF_ERR="error_log $NGINX_RSYSLOG_URL_ERR_NGINX;"
fi

envwrite -o /etc/nginx/conf.d/worker.conf /etc/nginx/worker.conf.template
envwrite -o /etc/nginx/nginx.conf /etc/nginx/nginx.conf.template
envwrite -o /etc/php/$PHPVER/fpm/php-fpm.conf /etc/php/$PHPVER/fpm/php-fpm.conf.template

touch /var/log/php-fpm.log /var/run/nginx.pid
chown -R "$SERVER_UID:$SERVER_GID" /var/log/phpfpm /run/php /var/log/php-fpm.log /var/log/nginx /var/lib/nginx /var/run/nginx.pid 


if [ -x "/app/bin/docker-boot" ]; then
    echo "Booting application..." 1>&2
    runas --user "$SERVER_UID" --group "$SERVER_GID" --home /tmp /app/bin/docker-boot
    echo "Application booted" 1>&2
fi


if [ -n "$CRON_JOBS" ]; then
    echo "Starting cron" 1>&2
    (
        echo "SYMFONY_ENV='$SYMFONY_ENV'"
        echo "SYMFONY_DEBUG='$SYMFONY_DEBUG'"
        echo "SERVER_UID='$SERVER_UID'"
        echo "SERVER_GID='$SERVER_GID'"
        echo "WORKER_ENV_BOOTED='cron'"
        echo "$CRON_JOBS"
    ) > /etc/cron.d/worker-cron-jobs
    cron -P
fi

echo "Starting webserver" 1>&2

runas --user "$SERVER_UID" --group "$SERVER_GID" --home /tmp "php-fpm$PHPVER"
exec runas --user "$SERVER_UID" --group "$SERVER_GID" --home /tmp nginx -g 'daemon off;'
