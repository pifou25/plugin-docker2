#!/bin/bash

rm /usr/local/bin/docker-compose
rm /usr/bin/docker-compose
if [ $(uname -m) == 'armv7l' ]; then
    sudo curl -L "https://github.com/docker/compose/releases/download/v2.0.1/docker-compose-linux-armv7" -o /usr/local/bin/docker-compose
else
    sudo curl -L "https://github.com/docker/compose/releases/download/v2.0.1/docker-compose-linux-$(uname -m)" -o /usr/local/bin/docker-compose
fi
sudo chmod +x /usr/local/bin/docker-compose
sudo ln -s /usr/local/bin/docker-compose /usr/bin/docker-compose
