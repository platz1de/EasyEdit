# EasyEdit
Feature-rich WorldEditor for PocketMine-MP

## Features

- async Editing prevents server lag
- lots of different Commands
- support for unique Patterns
- Wooden Axe as Wand
- undo & redo 

## Commands

\<argument> - required Argument<br>
[argument] - optional Argument

Command | Description | Permission | Aliases
---|---|---|---
//pos1 | Set the first Position | easyedit.position | //1<br>left click a block in creative with a wooden axe
//pos2 | Set the first Position | easyedit.position | //2<br>break a block in creative with a wooden axe
//set \<pattern> | Set the selected Area | easyedit.command.set
//replace \<block> \<pattern> | Replace the selected Area | easyedit.command.set
//undo \<count>| Revert your latest change | easyedit.command.undo
//redo \<count> | Revert your latest undo | easyedit.command.redo
//copy | Copy the selected Area | easyedit.command.copy
//paste | Paste the Clipboard | easyedit.command.paste
//insert | Insert the Clipboard | easyedit.command.paste
//center [block] | Set the center Blocks (1-8) | easyedit.command.set | //middle