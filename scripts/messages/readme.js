const fs = require('node:fs');

const template = fs.realpathSync('languages/readme.pot');

const quote = (text) => text.replaceAll('"', '\\"');
const addEntry = (message, description, priority = null) => {
  const lines = [
    '',
    `#. ${description}`,
  ];

  if (priority) {
    lines.push(`#, gp-priority: ${priority}`);
  }

  lines.push(`msgid "${quote(message.trim())}"`);
  lines.push(`msgstr ""`);
  lines.push('');

  return lines.join('\n');
};

fs.readFile('README.txt', { encoding: 'utf8' }, (err, content) => {
  if (err) {
    throw err;
  }

  let newContent = `
# Copyright (C) 2023-2024 Stancer / Iliad 78
# This file is distributed under the MIT.
msgid ""
msgstr ""
"Project-Id-Version: $[last-commit-hash]\\n"
"Report-Msgid-Bugs-To: https://gitlab.com/wearestancer/cms/woocommerce/-/issues\\n"
"Last-Translator: $[last-commit-author]\\n"
"Language-Team: LANGUAGE <LL@li.org>\\n"
"MIME-Version: 1.0\\n"
"Content-Type: text/plain; charset=UTF-8\\n"
"Content-Transfer-Encoding: 8bit\\n"
"POT-Creation-Date: $[first-commit-date]\\n"
"PO-Revision-Date: $[last-commit-date]\\n"
`.trimStart();

  const parts = content
    .replaceAll(/\[(.+)\]\((.+)\)/g, (_, text, link) => `<a href="${link}">${text}</a>`)
    .split('\n\n')
    .map((v) => v.trim())
  ;
  const first = parts.splice(0, 1).at(0).split('\n').at(0).replaceAll('=', '').trim();
  let priority = 'high';
  let section = 'description';

  newContent += addEntry(first, 'Plugin name.', priority);

  for (const line of parts) {
    let subsection = 'paragraph';

    if (line.startsWith('=')) {
      priority = null;
      subsection = 'header';

      if (line === '== Installation ==') {
        section = 'installation';
      } else if (line === '== Changelog ==') {
        section = 'changelog';
      }

      if (line.startsWith('= Version')) {
        subsection = 'list item';

        for (const changes of line.split('\n')) {
          if (changes.startsWith('*')) {
            newContent += addEntry(changes.substring(1), `Found in ${section} ${subsection}.`, 'low');
          }
        }
      } else {
        newContent += addEntry(line.replaceAll('=', ''), `Found in ${section} ${subsection}.`);
      }
    } else {
      if (line.startsWith('1. ')) {
        subsection = 'list item';

        for (const enumeration of line.split(/\d+\./g)) {
          if (enumeration) {
            const tmp = enumeration.split('\n').map((v) => v.trim()).filter((v) => v).join('\\n');

            newContent += addEntry(tmp, `Found in ${section} ${subsection}.`, priority);
          }
        }
      } else {
        newContent += addEntry(line.replace('\n', '\\n').replace(/^\*/, ''), `Found in ${section} ${subsection}.`, priority);
      }
    }
  }

  fs.writeFile(template, newContent, (err) => {
    if (err) {
      throw err;
    }
  });
});
