# How to use it
[![License](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://img.shields.io/badge/License-GPL%20v2-blue.svg)   

## Install
`wp package install codeatcode/wp-cli-getbyurl`

### Output structure
`Kind of content | post id | post type/taxonomy`

## Commands
To get the id and post type by url:

`wp get-by-url --skip-plugins --skip-themes http://demo.test/2018/10/your-post`

Output:

`post | 33 | post`

To get the id and taxonomy by url:

`wp get-by-url --skip-plugins --skip-themes http://demo.test/tag/test/`

Output:

`term | 2 | post_tag`

### Why skip plugins and themes

It is a good practice in WP CLI when you have to execute commands that are heavy of resources to not load plugins and themes for a lot of reasons.

### Why the separator is pipe?

In that way is more easy to do script on it in bash or other languages

## How to use it

```#!/bin/bash

# How to use the script:
#     cat list.txt | xargs -n1 remove-page-by-url.sh

out=$(wp get-by-url "$1" --skip-plugins --skip-themes)
if [[ -n $out ]]; then
    command=$(cut -d'|' -f1 <<< "$out" | sed 's/^[[:blank:]]*//;s/[[:blank:]]*$//')
    id=$(cut -d'|' -f2 <<< "$out" | sed 's/^[[:blank:]]*//;s/[[:blank:]]*$//')
    taxonomy=$(cut -d'|' -f3 <<< "$out" | sed 's/^[[:blank:]]*//;s/[[:blank:]]*$//')
    if [[ "$taxonomy" == 'post' ]]; then
        wp "$command" delete "$id" --skip-plugins --skip-themes
    else
        wp "$command" delete "$taxonomy" "$id" --skip-plugins --skip-themes
    fi
fi
```
