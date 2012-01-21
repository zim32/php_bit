README
========

Overview
---------
PhpBit is a little OOP library created to make work with Stream of Bits more easy   
      

There are 3 types of numbers: Byte, Word and Dword. Sizes are 8, 16 and 32 bits respectively

Usage
---------
#### Create number ####

    $byte = new Byte(0xF0);
    echo $byte; // 11110000
or

    $byte = new Byte("11110000");
    echo $byte; // 11110000   

#### Work with bits ####

    $byte->setBit(1,0); //                       01110000
    $byte->setBit(8,1); //                       01110001
    $byte->invert(); //                          10001110
    $byte->shiftLeft(1); //                      00011100
    $byte->shiftRight(1); //                     00001110
    $byte->makeOr(new Byte("10000000")); //      10001110  

#### Create more nums ####

    $word = new Word($byte, $byte);
    echo $word; //                               1000111010001110

#### Format output ####

    echo $word->toS(8, " | "); //                10001110 | 10001110 |
    echo $word->toHexString(); //                8E8E

#### Create stream  ####

    $stream = new Stream();
    $stream->add($word);
    $stream->add($word);
    $stream->add(new Byte(0xF0));
    echo $stream; //                             1000111010001110 1000111010001110 11110000 

#### Pack stream and dump to file  ####

    file_put_contents("stream", $stream->pack());
or  

    file_put_contents("stream", $stream->pack(Stream::PACK_MODE_LITTLEENDIAN));

#### Unpack stream and get access to bits  ####

    $data = file_get_contents("stream");
    $format = "2|2|1";
    $stream = Stream::createFrom($data, $format);
    echo $stream; //                           1000111010001110 1000111010001110 11110000
    echo var_dump($stream->get(3)->getBit(1)); // 1