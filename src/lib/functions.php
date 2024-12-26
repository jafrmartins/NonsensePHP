<?php


if(!function_exists(("binary_encode"))) {


    function binary_encode($str){
        
        # Declare both Binary variable and Prepend variable
        $bin = ""; $prep = "";
        
        # Iterate through each character of our input ($str) 
        for($i = 0; $i < strlen($str); $i++){
            
            # Encode The current character into binary
            $bincur = decbin( ord( $str[$i] ) );
            
            # Count the length of said binary
            $binlen = strlen( $bincur );
            
            # If the length of our character in binary is less than a byte (8 bits); Then
            # For how ever many characters it is short;
            # it will replace with 0"s in our Prepend variable.
            if( $binlen < 8 ) for( $j = 8; $j > $binlen; $binlen++ ) $prep .= "0"; 
            
            # Build our correct 8 bit string and add it to our Binary variable
            $bin .= $prep.$bincur." ";
            
            # Clear our Prepend variable before the next Loop
            $prep = "";

        }

        # Return the final result minus the one whitespace at the end
        # (from our for loop where we build the 8 bit string
        return substr($bin, 0, strlen($bin) - 1);

    }
    
}

if(!function_exists(("binary_decode"))) {

    function binary_decode($bin){
        $char = explode(" ", $bin);
        $nstr = "";
        foreach($char as $ch) $nstr .= chr(bindec($ch));
        return $nstr;
    }

}