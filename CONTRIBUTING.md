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

### Workflow for testing and merging pull requests

Part of opening a pull request is to describe how reviewers should test your work. MIK uses two different test workflows, "smoke test" and "testable":

* Smoke tests are required if the work you are contributing is not fully covered by PHPUnit tests. In other words, a human needs to test your work to confirm it does what it is intended to do and that it doesn't introduce any side effects. If your work needs to be tested using a smoke test, you are expected to provide sample configuration files and input data to allow the reviewer to perform the smoke tests.
* Testable work is work that can be tested by existing or new PHPUnit tests.

The following is the standard workflow that reviewers of pull requests against MIK use;

1. Person working on issue must incorporate the change into one of the existing PHPUnit tests, or provide new tests as applicable.
1. Person working on the issue must:
    a. state in the PR template that the tests pass on their local dev copy,
    b. summarize how the tests apply to the code changes in the PR, and
    c. indicate the expected number of successful tests and assertions.
1. Person reviewing PR clones branch, and runs the tests.
1. If the tests pass on the reviewer's local copy, and the reviewer agrees that the test code does in fact covers the code changes, the reviewer can decide if they want to merge into master without performing further smoke tests. Reviewer also has the option of deciding that the tests are not sufficient or that a smoke test involving sample data and configuration is justified.
1. If the person working on the issue does not provide a PHPUnit test, a smoke test is required prior to merging.

## License Agreements

MIK is licensed under GPL version 3 or higher. By opening a pull request or otherwise contributing code to the MIK codebase, you transfer non-exclusive ownership of that code (you retain ownership of your code for other purposes) to the MIK maintainers for the sole purpose of redistributing your contribution within the MIK codebase under the conditions of the GPLv3 license or higher. You also warrant that you have the legal authority to make such a transfer.

## Thanks

This CONTRIBUTING.md file is based heavily on the CONTRIBUTING.md file included with Islandora Foundation modules.
