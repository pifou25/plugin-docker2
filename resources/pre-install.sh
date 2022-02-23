#!/bin/bash

curl -fsSL https://download.docker.com/linux/debian/gpg | sudo gpg --no-tty --yes --dearmor -o /usr/share/keyrings/docker-archive-keyring.gpg

arch=`dpkg --print-architecture`;
echo "Docker arch found : "$arch

echo "deb [arch=$(dpkg --print-architecture) signed-by=/usr/share/keyrings/docker-archive-keyring.gpg] https://download.docker.com/linux/debian $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list > /dev/null

apt-get update