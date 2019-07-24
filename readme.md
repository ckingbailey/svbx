This project now (as of commit d05d25a, 4/25/2018) uses [Composer](https://getcomposer.org) to manage php dependencies

It also uses [npm](https://npmjs.com) to manage [node](https://nodejs.org) dependencies. There's a build step using [Gulp](https://gulpjs.com) that just moves front-end dependencies into the public folders.

Reminder to self:
### How I am running the thing locally
1. Start MAMP. You can do this from the command line:
```bash
$ mampstart
```
2. MAMP will open your default browser to a MAMP start page. I have an Apache virtual host setup to serve the project at a local subdomain. Point your browser to svbx.localhost:8888
