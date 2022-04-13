# EasyEdit

[![](https://poggit.pmmp.io/shield.state/EasyEdit)](https://poggit.pmmp.io/p/EasyEdit)

Feature-rich WorldEditor for PocketMine-MP

## Features

- large variety of commands
- High performance:
    - async editing, allowing the server to run normally while editing in the background
    - low memory consumption by splitting your actions into multiple smaller edits
- support for unique Patterns
    - set blocks in effectively infinite ways
    - see [Pattern Documentation](#Patterns)
- selection axe & brushes
- undo & redo your actions
- tile support
- load & save java selections (load MCEdit & Sponge format, save to Sponge)
- rotate & flip support
- Translation support
    - [help translate EasyEdit](https://github.com/platz1de/EasyEdit/blob/dev/resources/lang/TRANSLATE.md)

## Commands

\<argument> - required Argument<br>
[argument] - optional Argument

Selection:

| Command                                           | Description                                                  | Permission      | Aliases/Notice                                                                  |
|---------------------------------------------------|--------------------------------------------------------------|-----------------|---------------------------------------------------------------------------------|
| //pos1 [x] [y] [z]                                | Set the first position                                       | easyedit.select | //1<br>left click a block in creative with a wooden axe                         |
| //pos2 [x] [y] [z]                                | Set the first position                                       | easyedit.select | //2<br>break a block in creative with a wooden axe                              |
| //extend [direction] [count]<br>//extend vertical | Extend the selected area                                     | easyedit.select | //expand<br>Look into the direction you want to extend to                       |
| //set \<pattern>                                  | Set blocks in the selected area                              | easyedit.edit   |
| //replace \<block> \<pattern>                     | Replace blocks in the selected area                          | easyedit.edit   |
| //replace \<pattern>                              | Replace all solid blocks in the selected area                | easyedit.edit   |
| //overlay \<pattern>                              | Overlay blocks in the selected area                          | easyedit.edit   | Set blocks above existing blocks                                                |
| //naturalize \[pattern] \[pattern] \[pattern]     | Naturalize the selected area                                 | easyedit.edit   |
| //smooth                                          | Smooth the selected area                                     | easyedit.edit   |
| //center [pattern]                                | Set the center blocks of the selected area                   | easyedit.edit   | //middle                                                                        |
| //walls [pattern]                                 | Set the walls of the selected area                           | easyedit.edit   | //wall                                                                          |
| //sides [pattern]                                 | Set the sides of the selected area                           | easyedit.edit   | //side                                                                          |
| //move [direction] [count]                        | Move the selected area                                       | easyedit.edit   | Look into the direction you want the selected blocks to move into               |
| //stack [direction] [count]                       | Stack the selected area                                      | easyedit.edit   | Look into the direction you want the selected blocks to stack into              |
| //istack [direction] [count]                      | Stack the selected area without overwriting existing terrain | easyedit.edit   |
| //count [radius]                                  | Count blocks in the selected area                            | easyedit.select |
| //extinguish [radius]                             | Extinguish fire in the selected area                         | easyedit.edit   | //ext                                                                           |
| //view                                            | View the selected area                                       | easyedit.select | //show<br>also allows exporting as a 3d model (thank mojang for buggy textures) |

History:

| Command                  | Description                  | Permission                                         | Aliases/Notice             |
|--------------------------|------------------------------|----------------------------------------------------|----------------------------|
| //undo [count]           | Revert your latest change    | easyedit.history easyedit.edit                     |                            |
| //undo \<target> [count] | Revert targets latest change | easyedit.history easyedit.edit easyedit.edit.other | Can be disabled via config |
| //redo [count]           | Revert your latest undo      | easyedit.history easyedit.edit                     |                            |
| //redo \<target> [count] | Revert targets latest undo   | easyedit.history easyedit.edit easyedit.edit.other | Can be disabled via config |

Clipboard:

| Command                          | Description                                         | Permission                            | Aliases/Notice                                                    |
|----------------------------------|-----------------------------------------------------|---------------------------------------|-------------------------------------------------------------------|
| //copy<br>//copy center          | Copy the selected area                              | easyedit.clipboard                    |                                                                   |
| //cut<br>//cut copy              | Cut the selected area and copy it to your clipboard | easyedit.clipboard easyedit.edit      | Copies and replaces with air                                      |
| //paste                          | Paste the clipboard                                 | easyedit.clipboard easyedit.edit      |                                                                   |
| //insert                         | Insert the clipboard                                | easyedit.clipboard easyedit.edit      | Paste only into air blocks                                        |
| //rotate                         | Rotate the clipboard                                | easyedit.clipboard                    | Rotates by 90 Degrees                                             |
| //flip [direction]               | Flip the clipboard, mirroring at copied position    | easyedit.clipboard                    | Flips on axis you look on, always uses selected point as "mirror" |
| //loadschematic \<schematicName> | Load a saved schematic from disk                    | easyedit.readdisk easyedit.clipboard  | //load                                                            |
| //saveschematic \<schematicName> | Save your clipboard as a schematic to disk          | easyedit.writedisk easyedit.clipboard | //save                                                            |

Generation:

| Command                                                | Description                            | Permission                      | Aliases/Notice         |
|--------------------------------------------------------|----------------------------------------|---------------------------------|------------------------|
| //sphere \<radius> \<pattern>                          | Generate a sphere                      | easyedit.generate easyedit.edit | //sph                  |
| //hsphere \<radius> \<pattern> [thickness]             | Generate a hollow sphere               | easyedit.generate easyedit.edit | //hsph //hollowsphere  |
| //cylinder \<radius> \<height> \<pattern>              | Generate a cylinder                    | easyedit.generate easyedit.edit | //cy                   |
| //hcylinder \<radius> \<height> \<pattern> [thickness] | Generate a hollow cylinder             | easyedit.generate easyedit.edit | //hcy //hollowcylinder |
| //noise                                                | Generate using a simple noise function | easyedit.generate easyedit.edit |                        |

Utility:

| Command                                                                | Description                 | Permission                                      | Aliases/Notice                                                                            |
|------------------------------------------------------------------------|-----------------------------|-------------------------------------------------|-------------------------------------------------------------------------------------------|
| //commands [page]                                                      | List all EasyEdit commands  | -                                               | //h //cmd                                                                                 |
| //brush sphere \[radius] \[pattern] [gravity]                          | Create a spherical Brush    | easyedit.brush <br> (To use: easyedit.edit)     | //br sph                                                                                  |
| //brush smooth \[radius]                                               | Create a smoothing Brush    | easyedit.brush <br> (To use: easyedit.edit)     | //br smooth                                                                               |
| //brush naturalize \[radius] \[topBlock] \[middleBlock] \[bottomBlock] | Create a naturalize Brush   | easyedit.brush <br> (To use: easyedit.edit)     | //br nat                                                                                  |
| //brush cylinder \[radius] \[height] \[pattern] [gravity]              | Create a cylindrical Brush  | easyedit.brush <br> (To use: easyedit.edit)     | //br cy                                                                                   |
| //brush paste [insert]                                                 | Create a pasting Brush      | easyedit.brush <br> (To use: easyedit.edit)     | //br paste                                                                                |                                                                                                                                                                                                                                  |                             |                                                 |                                                                                          
| //fill \<Block> [direction]                                            | Fill an area                | easyedit.edit easyedit.generate                 | Fills into looking direction                                                              |
| //drain                                                                | Drain an area               | easyedit.edit easyedit.generate                 | 
| //line \<x> \<y> \<z> \[pattern]                                       | Draw a line                 | easyedit.edit easyedit.generate                 |                                                                                           |
| //blockinfo                                                            | Get a blockinfo stick       | easyedit.util                                   | //bi                                                                                      |
| //builderrod                                                           | Get a builder rod           | easyedit.rod                                    | //rod<br>Expands the clicked blockface                                                    |
| //status                                                               | Check on the EditThread     | easyedit.manage                                 |                                                                                           |
| //cancel                                                               | Cancel the current task     | easyedit.manage                                 |                                                                                           |
| //benchmark                                                            | Start a benchmark           | easyedit.manage                                 | This will create a temporary world and edit a few preset actions                          |
| //pastestates                                                          | Paste all known blockstates | easyedit.edit easyedit.generate easyedit.manage | Mainly for debugging (can be used as an oversight of all existing blocks though)          |
| //wand                                                                 | Get a wooden Axe            | easyedit.util                                   | Every normal axe works as well (as long as you have permissions and are in creative mode) |

## Patterns

Visit the [wiki](https://github.com/platz1de/EasyEdit/wiki/Patterns) for more information.

### Block Patterns

Block Patterns are just blocks, they just consist out of the name of the block or its numeric ID

Examples:<br>

- stone
- 4
- red_wool
- stone:1

The keyword "hand" represents the block you hold in your hand (or air for items/nothing) and can be used like normal blocks

### Random Pattern

The Random Pattern as it name suggests selects a random Pattern<br>
The patterns are separated by a comma and can be used in any order

Examples:<br>
```dirt,stone,air```<br>
```red_wool,green_wool,yellow_wool,orange_wool```

### Weighted Patterns

When one pattern should be more likely than another, the weighted notation can be used: <br>
```propability%pattern```

Example: <br>
```70%dirt,30%grass```

If the sum of given percentages is smaller than 100, there is a chance to not change anything:<br>
```10%stone,10%dirt``` - 80% of the selected area will not be affected

If the sum of given percentages is greater than 100, given probabilities are scaled accordingly:<br>
```150%stone,50%dirt``` - 75% will be set to stone, 25% will be set to dirt

## Complex Patterns

Complex patterns follow strict rules and as such allow the creation of complex structures

Usage of Complex Patterns: patternName;arg1;arg2...(subPattern1,subPattern2...)

Complex patterns can be chained together with dots to create a logic construct: <br>
```block;stone(dirt).grass``` - Replace all stone blocks with dirt and everything else with grass

Chained constructs are executed from left to right until a valid block is found, otherwise the block will stay unaffected

They can also be used with the default comma notation and are selected randomly, or in combination: <br>
```stone,block;stone(dirt).grass,wool``` - Places either stone, wool or following the pattern described above

### Logic Patterns

These Patterns allow control over when to set certain blocks

These are especially useful in complex structures or even nested: <br>
```odd;x(odd;z(black_wool).white_wool).odd;z(white_wool).black_wool``` - A 2d checkers pattern

\<argument> - required Argument<br>
[argument] - optional Argument<br>
patterns - children patterns, can be separated by a comma

| Pattern                                      | Description                                                                                                                               |
|----------------------------------------------|-------------------------------------------------------------------------------------------------------------------------------------------|
| block;\<block>(patterns)                     | Executes Patterns if the block is the same as the specified block (like in //replace)                                                     |
| solid(patterns)                              | Executes Patterns if the block is not an air-like block (ignored in config)                                                               |
| above;\<block>(patterns)                     | Executes Patterns if the block is above the specified block                                                                               |
| around;\<block>(patterns)                    | Executes Patterns if the block is next to the specified block                                                                             |
| horizontal;\<block>(patterns)                | Executes Patterns if the block is next to the specified block in horizontal direction                                                     |
| below;\<block>(patterns)                     | Executes Patterns if the block is below the specified block                                                                               |
| not(condition(patterns))                     | Executes Patterns of next Pattern is false (only works when nested)                                                                       |
| odd;\[x];\[y];\[z](patterns)                 | Executes Patterns if the block is at odd coordinates at x, y and z Axis, the x, y and z can be left out (only given ones will be checked) |
| even;\[x];\[y];\[z](patterns)                | Executes Patterns if the block is at even coordinates (see odd for more info)                                                             |
| divisible;\<number>;\[x];\[y];\[z](patterns) | Executes Patterns if the block is at coordinates which are divisible by the given number (see odd for more info)                          |
| walls;\[thickness](patterns)                 | Executes Patterns if the block is one of the walls of the selections                                                                      |
| sides;\[thickness](patterns)                 | Executes Patterns if the block is one of the sides of the selections (walls + bottom and top)                                             |
| embed;\<block>(patterns)                     | Executes Patterns if the block is around a higher specified block                                                                         |

### Functional Patterns

These Patterns have a unique use and are mostly used for the default commands

\<argument> - required Argument<br>
[argument] - optional Argument<br>
patterns - children patterns, can be separated by a comma

| Pattern                                      | Description                                                                       |
|----------------------------------------------|-----------------------------------------------------------------------------------|
| naturalize(\[pattern],\[pattern],\[pattern]) | makes your selection more natural (1 layer pattern1, 3 layers pattern2, pattern3) |
| gravity(\[pattern])                          | makes your blocks fall down until they reach the ground                           |

## Blame Mojang

Minecraft Bedrock is just terrible at a lot of things, we can't do anything about it.

### Delayed chunk updates

Chunks are not updated immediately, this produces ugly xray like effects even if we sent everything needed

### Command Handling

Commands starting with a slash are handled terribly. The autocompletion just adds another slash every time and never shows the correct arguments making argument autocompletion impossible. For some non-apparent reason client side commands with an extra slash before them (like //help or //?) throw a client sided error and never send any packets to the server.