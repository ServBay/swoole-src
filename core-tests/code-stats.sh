#!/bin/sh -e
__CURRENT__=$(pwd)
__DIR__=$(cd "$(dirname "$0")";pwd)

# enter the dir
cd "${__DIR__}"
cloc . --exclude-dir=js,Debug,CMakeFiles,build,CMakeFiles,deps
