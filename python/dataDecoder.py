#!/usr/bin/python

import os, sys, getopt, dataFileParser  


def main(argv):
#   print str(sys.argv) + "\n"
   inputfile = None
   outputdir = os.path.dirname(os.path.abspath(__file__))
   e_magneto = False
   e_accelero = False
   e_battery = True
   e_gps_csv = True
   
   try:
      opts, args = getopt.getopt(argv,"hgambi:o:",["inputfile=","outputdir="])
   except getopt.GetoptError:
      print sys.argv[0] + ' -[gamb] -i <inputfile> -o <outputdir>'
      sys.exit(2)
   for opt, arg in opts:
      if opt == '-h':
         print sys.argv[0] + ' -[gamb] -i <inputfile> -o <outputfile>'
         sys.exit()
      elif opt in ("-i",  "--inputfile"):
         inputfile = arg
      elif opt in ("-o", "--outputdir"):
         outputdir = arg
      elif opt in ("-m"):
         e_magneto = True
      elif opt in ("-a"):
         e_accelero = True
      elif opt in ("-b"):
         e_battery = True
      elif opt in ("-g"):
         e_gps_csv = True         
   
   print 'Output dir is ', outputdir
   print 'Input file is ', inputfile
   
   if inputfile==None:
       print 'Missing input file'
       print sys.argv[0] + ' -[gamb] -i <inputfile> -o <outputdir>'
       sys.exit(2)
    
   params = [e_magneto, e_accelero, e_battery, e_gps_csv];
   parser = dataFileParser.DataFileParser(None,params,'console',1, None)
   basename = os.path.basename(inputfile).split('.')[0]
   
   prefix = outputdir + '/'+ basename
   parser.convertFile(inputfile, prefix)
    

if __name__ == "__main__":
   main(sys.argv[1:])
