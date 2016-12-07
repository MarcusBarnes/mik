# Contributing to MIK

All contributions to MIK are welcome: use-cases, documentation, code, bug reports, feature requests, etc. You do not need to be a programmer to contribute!

Regardless of how you want to contribute to MIK, start by opening a Github issue. Someone (probably one of the maintainers) will respond and keep the discussion going.

### Write some documentation

If you use MIK and you have documented a task for yourself, consider sharing it with other users. We'd be happy to put it on the MIK wiki or link to it if you'd rather maintain it somewhere else.

### Request a new feature

We love hear about how you want to use MIK! In order to help us understand a new feature request, we ask you to provide us with a structured use case following this template:

| Title (Goal)  | The title or goal of your use case                            |
--------------- |------------------------------------                           |
| Primary Actor | Repository architect, metadata specialist, repository admin   |
| Scope         | The scope of the feature. Example: usability, performance     |
| Level         | The priority the use case should be given; High, Medium, Low  |
| Story         | A paragraph of text describing how this feature should work a what it should accomplish |

***

**Additional examples**:
* One per list bullet

**Additional Remarks**:
* One per list bullet

### Report a bug

To report a bug you should open an issue that summarizes the bug. Set the Issue Type to "Bug".

In order to help us understand and fix the bug it would be useful if you could provide us with:

1. The steps to reproduce the bug. This includes information about e.g. the Islandora version you were using along with version of stack components.
2. If applicble, some sample data that triggers the bug.
3. The expected behavior.
4. The current, incorrect behavior.

Feel free to search the issue queue for existing issues that already describe the problem; if there is such a ticket please add your information as a comment.

**If you want to provide a pull along with your bug report:**

In this case please send us a pull request as described in section _Create a pull request_ below.

### Contribute code

Contributions to the Islandora codebase should be sent as GitHub pull requests. See section _Create a pull request_ below for details. If there is any problem with the pull request we can work through it using the commenting features of GitHub.

* For all code contributions, please use the following process in order to to prevent any wasted work and catch design issues early on.

    1. [Open an issue](https://github.com/MarcusBarnes/mik/issues) and assign it the label of "enhancement" or "feature request", if a similar issue does not exist already. If a similar issue does exist, then you should consider participating in the work on the existing issue.
    2. Comment on the issue with your plan for implementing the issue. Explain what pieces of the codebase you are going to touch and how everything is going to fit together.
    3. The MIK maintainers will work with you on the design to make sure you are on the right track.
    4. Implement your issue, create a pull request (see below), and iterate from there.

#### Issue / Topic Branches

All issues should be worked on in separate git branches. The branch name should be the same as the Github issue number, e.g., issue-243.

### Create a pull request

Take a look at [Creating a pull request](https://help.github.com/articles/creating-a-pull-request). In a nutshell you need to:

1. [Fork](https://help.github.com/articles/fork-a-repo) the MIK repository to your personal GitHub account. See [Fork a repo](https://help.github.com/articles/fork-a-repo) for detailed instructions.
2. Commit any changes to the issue/topic branch in your fork. Comments can be as terse as "Work on #243.", etc. but you can be more descriptive if you want. However, please refer to the issue you are working on somewhere in the commit comment using Github's '#' shortcut, as in the example.
3. Send a [pull request](https://help.github.com/articles/creating-a-pull-request) to the MIK GitHub repository that you forked in step 1 (in other words, https://github.com/MarcusBarnes/mik).

You may want to read [Syncing a fork](https://help.github.com/articles/syncing-a-fork) for instructions on how to keep your fork up to date with the latest changes of the upstream (official) `mik` repository.

## License Agreements

MIK is licensed under GPL version 3 or higher. By opening a pull request or otherwise contributing code to the MIK codebase, you transfer non-exclusive ownership of that code (you retain ownership of your code for other purposes) to the MIK maintainers for the sole purpose of redistributing your contribution within the MIK codebase under the conditions of the GPLv3 license or higher. You also warrant that you have the legal authority to make such a transfer.

## Thanks

This CONTRIBUTING.md file is based heavily on the CONTRIBUTING.md file included with Islandora Foundation modules.
