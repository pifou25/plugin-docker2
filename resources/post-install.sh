#!/bin/bash

rm /usr/local/bin/docker-compose
rm /usr/bin/docker-compose
sudo curl -L "https://github.com/docker/compose/releases/download/2.0.1/docker-compose-linux-$(uname -m)" -o /usr/local/bin/docker-compose
sudo chmod +x /usr/local/bin/docker-compose
sudo ln -s /usr/local/bin/docker-compose /usr/bin/docker-compose