# EasyEdit

[![](https://poggit.pmmp.io/shield.state/EasyEdit)](https://poggit.pmmp.io/p/EasyEdit)

Feature-rich WorldEditor for PocketMine-MP

## Features

- large variety of commands
- High performance:
    - async editing, allowing the server to run normally while editing in the background
    - low memory consumption by splitting your actions into multiple smaller ones
    - read more about performance [here](#Performance)
- flicker-free editing
- support for unique Patterns
    - set blocks in effectively infinite ways
    - see [Pattern Documentation](#Patterns)
- selection axe & brushes
- undo & redo your actions
- tile support
- load & save java selections (load McStructure, MCEdit & Sponge format, save to Sponge)
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
| //extend --min <x>,<y>,<z> --max <x>,<y>,<z>      | Modify the selected area by the given vectors                | easyedit.select | "Min" and "Max" refer to the corners of the selection                           |
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
| //count [radius] [-d]                             | Count blocks in the selected area                            | easyedit.select |
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

| Command                 | Description                                         | Permission                            | Aliases/Notice                                                    |
|-------------------------|-----------------------------------------------------|---------------------------------------|-------------------------------------------------------------------|
| //copy [--center]       | Copy the selected area                              | easyedit.clipboard                    |                                                                   |
| //cut [--center]        | Cut the selected area and copy it to your clipboard | easyedit.clipboard easyedit.edit      | Copies and replaces with air                                      |
| //paste                 | Paste the clipboard                                 | easyedit.clipboard easyedit.edit      |                                                                   |
| //insert                | Insert the clipboard                                | easyedit.clipboard easyedit.edit      | Paste only into air blocks                                        |
| //merge                 | Merge current terrain with the clipboard            | easyedit.clipboard easyedit.edit      | Paste only non-air blocks                                         |
| //rpaste                | Replace current terrain with the clipboard          | easyedit.clipboard easyedit.edit      | Replace non-air blocks with non-air blocks from the selection     |
| //rotate                | Rotate the clipboard                                | easyedit.clipboard                    | Rotates by 90 Degrees                                             |
| //flip [direction]      | Flip the clipboard, mirroring at copied position    | easyedit.clipboard                    | Flips on axis you look on, always uses selected point as "mirror" |
| //load \<schematicName> | Load a saved schematic from disk                    | easyedit.readdisk easyedit.clipboard  | //loadschematic                                                   |
| //save \<schematicName> | Save your clipboard as a schematic to disk          | easyedit.writedisk easyedit.clipboard | //saveschematic                                                   |

Generation:

| Command                                                | Description                            | Permission                      | Aliases/Notice         |
|--------------------------------------------------------|----------------------------------------|---------------------------------|------------------------|
| //cube \<size> \<pattern>                              | Generate a cube                        | easyedit.generate easyedit.edit | //cb                   |
| //hcube \<size> \<pattern> [thickness]                 | Generate a hollow cube                 | easyedit.generate easyedit.edit | //hcb //hollowcube     |
| //sphere \<radius> \<pattern>                          | Generate a sphere                      | easyedit.generate easyedit.edit | //sph                  |
| //hsphere \<radius> \<pattern> [thickness]             | Generate a hollow sphere               | easyedit.generate easyedit.edit | //hsph //hollowsphere  |
| //cylinder \<radius> \<height> \<pattern>              | Generate a cylinder                    | easyedit.generate easyedit.edit | //cy                   |
| //hcylinder \<radius> \<height> \<pattern> [thickness] | Generate a hollow cylinder             | easyedit.generate easyedit.edit | //hcy //hollowcylinder |
| //noise                                                | Generate using a simple noise function | easyedit.generate easyedit.edit |                        |

Utility:

| Command                                                                | Description                                 | Permission                                      | Aliases/Notice                                                                            |
|------------------------------------------------------------------------|---------------------------------------------|-------------------------------------------------|-------------------------------------------------------------------------------------------|
| //commands [page]                                                      | List all EasyEdit commands                  | -                                               | //h //cmd                                                                                 |
| //brush sphere \[radius] \[pattern] [gravity]                          | Create a spherical Brush                    | easyedit.brush <br> (To use: easyedit.edit)     | //br sph                                                                                  |
| //brush smooth \[radius]                                               | Create a smoothing Brush                    | easyedit.brush <br> (To use: easyedit.edit)     | //br smooth                                                                               |
| //brush naturalize \[radius] \[topBlock] \[middleBlock] \[bottomBlock] | Create a naturalize Brush                   | easyedit.brush <br> (To use: easyedit.edit)     | //br nat                                                                                  |
| //brush cylinder \[radius] \[height] \[pattern] [gravity]              | Create a cylindrical Brush                  | easyedit.brush <br> (To use: easyedit.edit)     | //br cy                                                                                   |
| //brush paste [insert]                                                 | Create a pasting Brush                      | easyedit.brush <br> (To use: easyedit.edit)     | //br paste                                                                                |                                                                                                                                                                                                                                  |                             |                                                 |                                                                                          
| //fill \<Block> [direction]                                            | Fill an area                                | easyedit.edit easyedit.generate                 | Fills into looking direction                                                              |
| //drain                                                                | Drain an area                               | easyedit.edit easyedit.generate                 |
| //line \<x> \<y> \<z> \[pattern]                                       | Draw a line                                 | easyedit.edit easyedit.generate                 |                                                                                           |
| //line [find\|solid] \<x> \<y> \<z> \[pattern]                         | Find a valid path to the destination (slow) | easyedit.edit easyedit.generate                 | "find" allows diagonals                                                                   |
| //blockinfo                                                            | Get a blockinfo stick                       | easyedit.util                                   | //bi                                                                                      |
| //builderrod                                                           | Get a builder rod                           | easyedit.rod                                    | //rod<br>Expands the clicked blockface                                                    |
| //status                                                               | Check on the EditThread                     | easyedit.manage                                 |                                                                                           |
| //cancel                                                               | Cancel the current task                     | easyedit.manage                                 |                                                                                           |
| //benchmark                                                            | Start a benchmark                           | easyedit.manage                                 | This will create a temporary world and edit a few preset actions                          |
| //pastestates                                                          | Paste all known blockstates                 | easyedit.edit easyedit.generate easyedit.manage | Mainly for debugging (can be used as an oversight of all existing blocks though)          |
| //wand                                                                 | Get a wooden Axe                            | easyedit.util                                   | Every normal axe works as well (as long as you have permissions and are in creative mode) |

Movement:

| Command   | Description                 | Permission                  | Aliases/Notice                                          |
|-----------|-----------------------------|-----------------------------|---------------------------------------------------------|
| //thru    | Teleport through blocks     | easyedit.util               | //t                                                     |
| //unstuck | Teleport to a safe location | easyedit.util               | //u                                                     |
| //up      | Teleport up                 | easyedit.util easyedit.edit | Places a glass block below you for simplified selecting |

## Patterns

Visit the [wiki](https://github.com/platz1de/EasyEdit/wiki/Patterns) for information on patterns.

All variants of bedrock and java states are supported.

## Schematics

To load external schematic files, place them in the "schematics" folder inside the EasyEdit plugin data folder. <br>
Just execute `//load <schematicName>` to load them and then use `//paste` to paste them. Omitting the name (`//load`) will list all available schematics.

To save a schematic, use `//save` <schematicName>. This will save the current clipboard (which was previous copied using `//copy`) to the schematics folder.

Schematics are fully compatible with the java edition. When loading or saving a schematic, the file will be automatically converted to the java format.<br>
This includes blocks and tile entities.

Supported Formats:
- Sponge
- McEdit (reading only)

## Performance

EasyEdit aims to be as fast as possible while still allowing for a ton of features. <br>
All operations are executed asynchronously, meaning that the server will not lag while editing. You can always check the status of the current operation using `//status`.

Other world editors usually load the entirety of the selection into memory, which makes your server run out of memory very quickly. <br>
EasyEdit counteracts this by loading chunks on the fly, only loading the blocks that are actually being edited at the moment. <br>
This means that you can edit huge areas without having to worry about your server crashing. There is practically no limit to how many blocks you can edit at once.