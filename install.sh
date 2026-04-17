# ensure running as root
if [ "$(id -u)" != "0" ]; then
    #Debian doesnt have sudo if root has a password.
    if ! hash sudo 2>/dev/null; then
        exec su -c "$0" "$@"
    else
        exec sudo "$0" "$@"
    fi
fi

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"

if [ ! -f "$SCRIPT_DIR/snipeit.sh" ]; then
    echo "Missing installer helper: $SCRIPT_DIR/snipeit.sh" 1>&2
    exit 1
fi

chmod 744 "$SCRIPT_DIR/snipeit.sh"
"$SCRIPT_DIR/snipeit.sh" 2>&1 | tee -a /var/log/codycloud-asset-install.log
