const fs = require("node:fs");
const exec = require("node:child_process").exec;

const template = fs.realpathSync("languages/stancer.pot");

exec(`wp i18n make-pot . ${template}`, (error) => {
  if (error) {
    throw error;
  }

  const content = fs.readFileSync(template, { encoding: 'utf-8' })
    .replace(/^# Copyright \(C\) (.+) Stancer$/m, '# Copyright (C) $1 Stancer / Iliad 78')
    .replace(/^"Report-Msgid-Bugs-To: .+"$/m, '"Report-Msgid-Bugs-To: https://gitlab.com/wearestancer/cms/woocommerce/-/issues\\n"')
  ;

  fs.writeFileSync(template, content, { encoding: 'utf-8' });
});
