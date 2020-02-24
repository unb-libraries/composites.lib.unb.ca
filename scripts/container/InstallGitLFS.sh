#!/usr/bin/env sh
curl -LO https://github.com/git-lfs/git-lfs/releases/download/v${GIT_LFS_VERSION}/git-lfs-linux-amd64-v${GIT_LFS_VERSION}.tar.gz
tar xvzpf git-lfs-linux-amd64-v${GIT_LFS_VERSION}.tar.gz
rm -rf git-lfs-linux-amd64-v${GIT_LFS_VERSION}.tar.gz
./install.sh
rm -rf README.md CHANGELOG.md git-lfs install.sh
