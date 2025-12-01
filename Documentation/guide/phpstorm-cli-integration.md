# PhpStorm: Ishmael CLI Integration and Aliases

This guide shows how to import an Ishmael CLI definition into PhpStorm and how to set up aliases for faster commands.

XML location inside Core (shipped to end users):
- IshmaelPHP-Core/extras/phpstorm/ish-cli.xml
- When installed via Composer: vendor/ishmael/ishmael-core/IshmaelPHP-Core/extras/phpstorm/ish-cli.xml

What you get
- Menu entries under Tools -> Ishmael CLI for common commands
- Command completion and inline help for defined commands
- Optional per-project aliases for very quick execution

Prerequisites
- Open the project at your application root (where composer.json lives)
- Ishmael Core installed so vendor/bin/ish exists

Windows uses vendor\\bin\\ish.bat. macOS/Linux uses vendor/bin/ish.

Step 1: Import the tool definition

1. Settings/Preferences -> Tools -> Command Line Tool Support
2. Click + and choose "Custom tool"
3. Click "Import" and select the XML from the path above
4. Set Alias to "ish" (or any alias you prefer)
5. Program path:
   - Windows: $ProjectFileDir$\\vendor\\bin\\ish.bat
   - macOS/Linux: $ProjectFileDir$/vendor/bin/ish
6. Working directory: $ProjectFileDir$
7. Apply / OK

You should now see a Tools -> Ishmael CLI menu with predefined commands (make:module, migrate, seed, etc.).

Step 2: Run a command from PhpStorm
- Tools -> Ishmael CLI -> help
- Or press Ctrl twice (Run Anything), type: ish help, then Enter

Step 3: Create handy aliases
1. Settings/Preferences -> Tools -> Command Line Tool Support
2. Select "Ishmael CLI" -> Open alias editor
3. Suggested aliases:
   1.  mkm -> make:module (Args: <Name>)
   2.  mkr -> make:resource (Args: <Module> <Name> [--api])
   3.  mkc -> make:controller (Args: <Module> <Name> [--invokable])
   4.  mks -> make:service (Args: <Module> <Name>)
   5.  mig -> migrate (Args: [--module=<Name>] [--steps=<N>] [--pretend])
   6.  rb -> migrate:rollback (Args: [--module=<Name>] [--steps=<N>])
   7.  status -> status (Args: [--module=<Name>])
   8.  seed -> seed (Args: [--module=<Name>] [--class=<FQCN>] [--force] [--env=<ENV>])
   9.  routes -> make:routes (Args: [<Module>] [--api])
   10. mods:cache -> modules:cache
   11. mods:clear -> modules:clear
   12. pack -> pack (Args: [--target=<dir>] [--out=<dir>] [--env=<env>] [--include-dev] [--dry-run])

Notes
- Keep aliases short and memorable
- Use placeholders in the Arguments field so PhpStorm prompts you when running

Updating the command list
- Re-import the XML after upgrading Core to get newly added commands
- Or add custom aliases manually for new commands

Troubleshooting
- Program not found: check the Program path matches your OS and that vendor/bin/ish exists
- Permission denied on macOS/Linux: run: chmod +x vendor/bin/ish
- Wrong working directory: ensure Working directory is $ProjectFileDir$

Repository placement and distribution
- The XML is stored inside the Core package at IshmaelPHP-Core/extras/phpstorm/ish-cli.xml so it is included when distributed via Composer
- End users can import it directly from vendor/ishmael/ishmael-core/IshmaelPHP-Core/extras/phpstorm/ish-cli.xml

Related
- See guide/cli.md for command semantics and examples
