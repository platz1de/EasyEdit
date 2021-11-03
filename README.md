# EasyEdit

[![](https://poggit.pmmp.io/shield.state/EasyEdit)](https://poggit.pmmp.io/p/EasyEdit)

Feature-rich WorldEditor for PocketMine-MP

**Note: please don't complain about slow chunk updates, this is caused by a minecraft bug present since 1.17
#BlameMojang**

## Features

- async editing prevents server lag
- large variety of commands
- selection splitting reduces effect of editing
- support for unique Patterns
- selection axe & brushes
- undo & redo your actions
- tile support

## Commands

\<argument> - required Argument<br>
[argument] - optional Argument

Command | Description | Permission | Aliases/Notice
---|---|---|---
//pos1 [x] [y] [z]| Set the first Position | easyedit.position | //1<br>left click a block in creative with a wooden axe
//pos2 [x] [y] [z]| Set the first Position | easyedit.position | //2<br>break a block in creative with a wooden axe
//set \<pattern> | Set the selected Area | easyedit.command.set
//replace \<block> \<pattern> | Replace the selected Area | easyedit.command.set
//naturalize \[pattern] \[pattern] \[pattern] | Naturalize the selected Area | easyedit.command.set
//smooth | Smooth the selected Area | easyedit.command.set
//brush sphere \[radius] \[pattern]<br>//brush smooth \[radius]<br>//brush naturalize \[radius] \[topBlock] \[middleBlock] \[bottomBlock]<br>//brush cylinder \[radius] \[height] \[pattern] | Create a new Brush | easyedit.command.brush | //br
//undo \<count>| Revert your latest change | easyedit.command.undo
//redo \<count> | Revert your latest undo | easyedit.command.redo
//copy | Copy the selected Area | easyedit.command.copy
//paste | Paste the Clipboard | easyedit.command.paste
//insert | Insert the Clipboard | easyedit.command.paste | Paste only into air blocks
//center [block] | Set the center Blocks (1-8) | easyedit.command.set | //middle
//walls [pattern] | Set walls of the selected area | easyedit.command.set | //wall
//sides [pattern] | Set sides of the selected area | easyedit.command.set | //side
//move <count> | Move the selected area | easyedit.command.paste | Look into the direction you want the selected blocks to move into
//stack <count> | Stack the selected area | easyedit.command.paste | Look into the direction you want the selected blocks to stack into
//extend [count\|vertical] | Extend the selected Area | easyedit.position | //expand<br>Look into the direction you want to extend to
//sphere \<radius> \<pattern> | Set a sphere | easyedit.command.set | //sph
//hsphere \<radius> \<pattern> [thickness] | Set a hollow sphere | easyedit.command.set | //hsph //hollowsphere
//cylinder \<radius> \<height> \<pattern> | Set a cylinder | easyedit.command.set | //cy
//hcylinder \<radius> \<height> \<pattern> [thickness] | Set a hollow cylinder | easyedit.command.set | //hcy //hollowcylinder
//count [radius] | Count selected blocks | easyedit.command.count
//blockinfo | Get a blockinfo stick | easyedit.command.info | //bi
//status | Check on the EditThread | easyedit.command.thread
//cancel | Cancel the current task | easyedit.command.thread

## Patterns

Patterns allow the creation of complex editing rules.

Usage of Patterns: #patternName;arg1;arg2...(block1,block2...)

### Block Patterns

Block Patterns are just blocks, they just consist out of the name of the block or its numeric ID

Examples:

- stone
- 4
- command_block
- stone:1

  The keyword "hand" represents the block you hold in your hand (or air for items/nothing) and can be used like normal
  blocks

### Random Pattern

The Random Pattern as it name suggests selects a random Pattern

Example:

```
#random(dirt,stone,air)
```

It can also be used with Logic Patterns, note that it only selects once, if the pattern is not valid nothing is changed

It also works nested:

```
#random(#random(stone,stone:1,stone:2),#random(dirt,grass))
```

### Logic Patterns

These Patterns allow control over the usage of Blocks

If one Pattern is not valid, the next one is being used (separated by a comma)

Example:

```
#block;stone(dirt),#around;stone(grass)
```

-> stone and blocks next to stone get replaced with dirt/grass, otherwise nothing happens

They can also be nested:

```
#block;stone(#around;dirt(grass)),air
```

-> stone blocks which also have dirt blocks next to them get replaced with grass, other stone blocks stay as they are,
non-stone blocks are set to air

\<argument> - required Argument<br>
[argument] - optional Argument<br>
patterns - children patterns, can be separated by a comma

Pattern | Description
---|---
#block;\<block>(patterns) | Executes Patterns if the block is the same as the specified block (like in //replace)
#above;\<block>(patterns) | Executes Patterns if the block is above the specified block
#around;\<block>(patterns) | Executes Patterns if the block is next to the specified block
#below;\<block>(patterns) | Executes Patterns if the block is below the specified block
#not(condition(patterns)) | Executes Patterns of next Pattern is false (only works when nested)
#odd;\[x];\[y];\[z](patterns) | Executes Patterns if the block is at odd coordinates at x, y and z Axis, the x, y and z can be left out (only given ones will be checked)
#even;\[x];\[y];\[z](patterns) | Executes Patterns if the block is at even coordinates (see odd for more info)
#divisible;\<number>;\[x];\[y];\[z](patterns) | Executes Patterns if the block is at coordinates which are divisible by the given number (see odd for more info)
#walls;\[thickness](patterns) | Executes Patterns if the block is one of the walls of the selections
#sides;\[thickness](patterns) | Executes Patterns if the block is one of the sides of the selections (walls + bottom and top)

### Functional Patterns

These Patterns have an own use

\<argument> - required Argument<br>
[argument] - optional Argument<br>
patterns - children patterns, can be separated by a comma

Pattern | Description
---|---
#smooth | makes your terrain smoother
#naturalize(\[pattern],\[pattern],\[pattern]) | makes your selection more natural (1 layer pattern1, 3 layers pattern2, pattern3)