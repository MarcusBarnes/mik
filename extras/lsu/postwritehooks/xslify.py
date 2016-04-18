#! /usr/bin/env python3

import argparse


def parse_our_args():
    parser = argparse.ArgumentParser(description="""XSL to apply to the xml.
        Include the XSL as arguments on the command line, separated by a space""")
    parser.add_argument('XSLs', help='xsl transformation to apply to the mods', type=str, nargs='*')
    args = parser.parse_args()
    return args.XSLs


if __name__ == '__main__':
    args_list = parse_our_args()
    print(args_list)
