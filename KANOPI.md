# Kanopi Studios Project

This document is used to describe the development and continuous maintenance and
 improvement of this project.

## About the Project
Support for the BC Intranet team

## Team
### Client
* Main point of contact: Meghan Stothers
* Client timezone: PT

### Kanopi
* Project Manager: Tim
* UX/Design: N/A
* Development Team: Jim, Blanca

## Important Links
* [Dev Site](http://dev-bcwork.pantheonsite.io/)
* [Dev Site Dashboard](https://dashboard.pantheon.io/sites/0ce50037-fecd-4de6-8a9c-c2ea6c845842)
* [Github](https://github.com/kanopi/work_next)
* [CircleCI](https://circleci.com/gh/kanopi/work_next)
* [Teamwork](https://kanopi.teamwork.com/#/projects/528734/overview/summary)

## Development Notes
Add notes as you go.  Which modules did you choose and why?  Did you try
anything that failed?  Write ongoing stories about the project/releases here.

## Documentation
Describe how we're documenting this project.  In some cases, the answer is "all
in code/annotations and nothing else".  In others it's a full
[MkDocs](https://www.mkdocs.org/) instance with user stories that are constantly
 updated throughout the life of the project.

## Acronyms
TMI - Too Much Information

## Things We Learned Together
Inside jokes and other fun memes bring teams together when working on projects,
but can feel exclusionary to new team members.  List your jokes and personable
notes here to help bring your new team members up to speed.

## Working local with Docksal

### Step #1: Docksal environment setup

**This is a one time setup - skip this if you already have a working Docksal environment.**

Follow [Docksal install instructions](https://docs.docksal.io/getting-started/setup/)

### Step #2: Project setup

1. Clone this repo into your Projects directory

    ```
    git clone https://github.com/kanopi/work_next.git drupal8
    cd work_next
    ```

2. Initialize the site

    This will initialize local settings and install the site via drush

    ```
    fin init
    ```

3. **On Windows** add `fin hosts add` to your hosts file

4. Point your browser to

    ```
    http://work-next.docksal
    ```

When the automated install is complete the command line output will display the admin username and password.

## Easier setup with 'fin init'

Site provisioning can be automated using `fin init`, which calls the shell script in [.docksal/commands/init](.docksal/commands/init).
This script is meant to be modified per project. The one in this repo will give you a good example of advanced init script.

Some common tasks that can be handled by the init script:

- initialize local settings files for Docker Compose, Drupal, Behat, etc.
- import DB or perform a site install
- compile Sass
- run DB updates, revert features, clear caches, etc.
- enable/disable modules, update variables values
