from datetime import datetime
import csv
import time
import calendar
import os

class DataFileParser:
    BYTEBUFFER_SIZE = 100000

    nbTrames = { 'total' : 0, 'info' : 0, 'accelero' : 0, 'magneto' : 0, 'gyro' : 0, 'gps' : 0, 'battery' : 0}
    startTime = None
    recordingPeriod = {'magneto' : 200, 'accelero' : 40, 'battery' : 1000*60*10} # recording period in ms.
    bufferTrame = { 'gps' : [], 'info' : [] }

    ## params [magneto, accelero, battery, gps]
    def __init__(self, parent=None, params=[True,True,True,True], logType='QGIS', logLevel=1, progressBar=None):
        """Constructor."""
        self.msg = []
        self.logType = logType
        self.logLevel = logLevel
        self.progressBar = progressBar
        self.e_magneto = params[0]
        self.e_accelero = params[1]
        self.e_gyro = False
        self.e_battery = params[2]
        self.e_gps = params[3]


    def log(self, msg, level=2,  noNewLine= False):
        if level<=self.logLevel:
            if self.logType=='console':
                print time.strftime("%d/%m/%Y %H:%M:%S") + ' : ' + msg, 
                print "\r", 
            else:
                self.msg.append(msg)

    def showProgess(self, progressPercent):
        if (self.progressBar!=None):
            self.progressBar.setValue(progressPercent)

    def analyseTrame(self, t):
        res = 'inconnu'
        typeByte = t[6]
        if typeByte=='I':
            res = 'info'
            if (str(t[0])+str(t[1]))=='Vb':
              res = 'batterie'
        elif typeByte=='A':
            res =  'accelero'
        elif typeByte=='M':
            res =  'magneto'
        elif typeByte=='R':
            res = 'gyroscope'
        elif typeByte=='G':
            res = 'gps'
        else:
            res = 'inconnu'
        return res

    def decodeTrameInf(self, t):
        strInf=str(t[0])+str(t[1])+str(t[2])+str(t[3])+str(t[4])+str(t[5])
        return strInf
    
    def parseDateInf(self,strDatetime):
        return time.strptime(strDatetime, "%d%m%y%H%M%S")
        
    def decodeTrameInf2(self):
        inf = []
        t0 = self.bufferTrame['info'].pop(0)
        str0 = self.decodeTrameInf(t0)
        str0 = str0.strip()
        # filtrage des info non pertinente
        if str0 in ['algo_0','algo_1']:
            return inf
            
        dtimeStr = ''
        if len(self.bufferTrame['info'])>0:
            if (str0 in ['start', 'stop','gps_1', 'gps_0', 'inactv']) or (str0[0:3] == 'ttf'):
                d = self.decodeTrameInf(self.bufferTrame['info'].pop(0))
                t = self.decodeTrameInf(self.bufferTrame['info'].pop(0))
                dtime = self.parseDateInf(d+t)
                dtimeStr = time.strftime("%Y-%m-%d %H:%M:%S", dtime)
                inf.append(str0)
                inf.append(dtimeStr)    
                inf.append(str0 + ';' + dtimeStr)
            #else:
            #    self.bufferTrame['info'].insert(0,str0)
        else:
            inf.append(str0)
            inf.append(dtimeStr)    
            inf.append(str0 + ';' + dtimeStr)
        return inf
    
    def filterTrameInf(self, tInf):
        exclusion = ['algo_0', 'algo_1']
        res = False
        for e in exclusion:
            res = res or (e in tInf)
        return (not res)
        
    def decodeTrameBat(self, t,i,start,period):
        bat = []
        timeMs=start + (i*period)
        bat.append(timeMs) # time en ms
        timeS=timeMs/1000
        deltaMs=timeMs%1000
        dtime=time.gmtime(timeS)
        dtimeStr=time.strftime("%Y-%m-%d %H:%M:%S",dtime)
        bat.append(dtimeStr) # datetime str
        v=str(t[2])+str(t[3])+str(t[4])+str(t[5])
        bat.append(v) # voltage
        bat.append(str(bat[0]) + ';' + str(bat[1]) + ';' + str(bat[2]))
        return bat

    def decodeTrameAMG(self, t,i,start,period):
        amg = []
        amg.append(self.decodeS16(t[0:2])) # ax
        amg.append(self.decodeS16(t[2:4])) # ay
        amg.append(self.decodeS16(t[4:6])) # az
        timeMs=start + (i*period)
        amg.append(timeMs) # time en ms
        timeS=timeMs/1000
        deltaMs=timeMs%1000
        dtime=time.gmtime(timeS)
        dtimeStr=time.strftime("%Y-%m-%d %H:%M:%S",dtime)
        amg.append(dtimeStr) # datetime str
        amg.append(deltaMs)  # delta ms
        amg.append(str(amg[0]) + ';' + str(amg[1]) + ';' + str(amg[2]) + ';' + str(amg[3]) + ';' + str(amg[4]) + ';' + str(amg[5]))
        return amg


    def decodeU32(self, t):
        res = ord(t[0])*256*256*256 + \
              ord(t[1])*256*256 + \
              ord(t[2])*256 + \
              ord(t[3])
        return res

    def decodeU16(self, t):
        res = ord(t[0])*256 + \
              ord(t[1])
        return res
    
    def decodeS16(self, t):
        res = (self.decodeU16(t) + 2**15) % 2**16 - 2**15
        return res
    
    def decodeLatLon(self, t):
        res = ((self.decodeU32(t) + 2**31) % 2**32 - 2**31)/1000000.0
        return res

    def decodeTrameGps(self):
        t0 = self.bufferTrame['gps'].pop(0);
        t1 = self.bufferTrame['gps'].pop(0);
        t2 = self.bufferTrame['gps'].pop(0);
        t3 = self.bufferTrame['gps'].pop(0);
        timestamp = self.decodeU32(t0[1:5])
        strDatetime = datetime.utcfromtimestamp(timestamp).strftime('%Y-%m-%d %H:%M:%S')
        tLat = str(t0[5])+ str(t1[0:3])
        tLon = str(t1[3:6]) + str(t2[0])
        lat = self.decodeLatLon(tLat)
        lon = self.decodeLatLon(tLon)
        nbSat = ord(t2[1])
        alt = (self.decodeU16(t2[2:4]) + 2**15) % 2**16 - 2**15
        hdop = self.decodeU16(t2[4:6])/100.0
        cap = self.decodeU16(t3[0:2])
        vit = self.decodeU16(t3[2:4])
        csvRow = '"' + strDatetime + '";' + \
                  str(lat) + ';' + str(lon)+ ';' + str(nbSat) + ';' + str(alt) + ';' +\
                  str(hdop) + ';' + str(cap) + ';' + str(vit)


        return [strDatetime,timestamp, lat, lon, nbSat, alt, hdop, cap, vit, csvRow]

    def gpsToCsv(self,prefix, withHeader=False):
        header = ['datetime','timestamp','lat','lon','nbsat','alt','hdop','cap','vit']
        if withHeader:
            mode = 'w'
        else:
            mode = 'a'
        with open(prefix+'_gps.csv', mode+'b') as csvfile:
            gpswriter = csv.writer(csvfile, delimiter=';',
                                quotechar='"', quoting=csv.QUOTE_MINIMAL)
            if withHeader:
                gpswriter.writerow(header)

            while(len(self.bufferTrame['gps'])>3):
                tGps = self.decodeTrameGps()
                gpswriter.writerow(tGps[0:9])
        csvfile.close()

    def amgToCsv(self, ts, prefix, suffix, start, freq, withHeader=False):
        header = ['X','Y','Z','time_ms','datetime','delta_ms']
        if withHeader:
            mode = 'w'
        else:
            mode = 'a'
        with open(prefix+'_'+suffix+'.csv', mode+'b') as csvfile:
            writer = csv.writer(csvfile, delimiter=';',
                                quotechar='"', quoting=csv.QUOTE_MINIMAL)
            if withHeader:
                writer.writerow(header)
            for i in range(len(ts)):
                #print tramesAcc[i]
                tMag = self.decodeTrameAMG(ts[i],i,start,freq)
                writer.writerow(tMag[0:6])
        csvfile.close()

    def infoToCsv(self, ts, prefix, suffix, withHeader=False):
        header = ['msg']
        if withHeader:
            mode = 'w'
        else:
            mode = 'a'
        with open(prefix+'_'+suffix+'.csv', mode+ 'b') as csvfile:
            writer = csv.writer(csvfile, delimiter=';',
                                quotechar='"', quoting=csv.QUOTE_MINIMAL)
            if withHeader:
                    writer.writerow(header)
            for i in range(len(ts)):
                tInfo=self.decodeTrameInf(ts[i])
                test=self.filterTrameInf(tInfo)
                if test==True:
                    writer.writerow([tInfo])
        csvfile.close()
     
    def infoToCsv2(self, prefix, suffix, withHeader=False):
        header = ['msg', 'datetime']
        if withHeader:
            mode = 'w'
        else:
            mode = 'a'
        with open(prefix+'_'+suffix+'.csv', mode+ 'b') as csvfile:
            writer = csv.writer(csvfile, delimiter=';',
                                quotechar='"', quoting=csv.QUOTE_MINIMAL)
            if withHeader:
                writer.writerow(header)

            while(len(self.bufferTrame['info'])>2):
                tInf = self.decodeTrameInf2()
                if len(tInf)>0:
                    writer.writerow(tInf[0:2])
        csvfile.close()
    def batToCsv(self, ts, prefix, suffix, start, freq, withHeader=False):
        header = ['time_ms','datetime','bat_mV']
        if withHeader:
            mode = 'w'
        else:
            mode = 'a'
        with open(prefix+'_'+suffix+'.csv', mode+'b') as csvfile:
            writer = csv.writer(csvfile, delimiter=';',
                                quotechar='"', quoting=csv.QUOTE_MINIMAL)
            if withHeader:
                writer.writerow(header)
            for i in range(len(ts)):
                tBat=self.decodeTrameBat(ts[i],i, start, freq)
                writer.writerow(tBat[0:3])
        csvfile.close()

    def parseInf(self, tInf):
        self.msgInf=[]
        for i in range(len(tInf)):
            msg=self.decodeTrameInf(tInf[i])
            self.msgInf.append(msg)
        try:
            d0=self.msgInf[2]
            t0=self.msgInf[3]
            dt0=d0+t0
            self.startTime=time.strptime(dt0, "%d%m%y%H%M%S")
            #d1=self.msgInf[-2]
            #t1=self.msgInf[-1]
            #dt1=d1+t1
            #self.stopTime=time.strptime(dt1, "%d%m%y%H%M%S")

        except Exception as e:
            self.startTime=time.strptime("010170000000", "%d%m%y%H%M%S")

    def convertFile(self, filename, prefix):
        self.log('effacement des fichiers')
        self.eraseOutputFiles(prefix)

        totalBytes = os.stat(filename).st_size
        self.log('Taille du fichier : ' + str(totalBytes))
        f = open(filename, "rb")
        trames=[]
        try:
            byteBuff = f.read(8)
            bytesRead = 8;
            totalBytesRead = 0
            while (byteBuff != ""):
                while (byteBuff != "") and (bytesRead<self.BYTEBUFFER_SIZE):
                    trames.append(byteBuff)
                    byteBuff = f.read(8)
                    bytesRead = bytesRead + 8;
                    #self.log("bytes read : " + str(bytesRead))
                totalBytesRead = totalBytesRead + bytesRead
                bytesRead = 0
                self.processTrames(trames, prefix)
                trames=[]
                self.log("percent processed : " + str(float(totalBytesRead)/float(totalBytes)*100.0), 1)
                progress = int(float(totalBytesRead)/float(totalBytes)*100.0)
                self.showProgess(progress)
        finally:
            f.close()
            self.log("fermeture fichier")
            self.logProcessInfo()

    def eraseOutputFiles(self, prefix):
        suffix = ['gps', 'accelero', 'magneto', 'gyro', 'info', 'battery']
        for s in suffix:
            outFileName = prefix + '_' + s + '.csv'
            if os.access(outFileName, os.F_OK):
                os.remove(outFileName)

    def processTrames(self, trames, prefix):
        self.nbTrames['total'] = self.nbTrames['total'] + len(trames);
        # dispatching trames
        tramesAcc=[]
        tramesMag=[]
        tramesGyr=[]
        tramesInf=[]
        tramesBat=[]
        self.tramesOther=[]
        for i in range(len(trames)):
            typeTrame = self.analyseTrame(trames[i])
            if typeTrame=='info':
                tramesInf.append(trames[i])
                self.bufferTrame['info'].append(trames[i])
            elif typeTrame=='batterie' and self.e_battery:
                tramesBat.append(trames[i])
            elif typeTrame=='accelero' and self.e_accelero:
                tramesAcc.append(trames[i])
            elif typeTrame=='magneto' and self.e_magneto:
                tramesMag.append(trames[i])
            elif typeTrame=='gyroscope' and self.e_gyro:
                tramesGyr.append(trames[i])
            elif typeTrame=='gps' and self.e_gps:
                self.bufferTrame['gps'].append(trames[i])


        # parsing starting info if not done...
        firstPass = self.startTime==None
        if firstPass:
            self.parseInf(tramesInf)
        startEpochMs = calendar.timegm(self.startTime)*1000 # start time in ms

        # specific decoding of trames
        for i in range(len(tramesInf)):
            self.log(self.decodeTrameInf(tramesInf[i]))

        if self.e_gps:
            self.nbTrames['gps'] = self.nbTrames['gps'] + (len(self.bufferTrame['gps'])/4)
            self.gpsToCsv(prefix, firstPass)
        if self.e_accelero:
            startAccelero = startEpochMs + self.nbTrames['accelero'] * self.recordingPeriod['accelero']
            self.amgToCsv(tramesAcc, prefix, 'accelero', startAccelero, self.recordingPeriod['accelero'], firstPass)
            self.nbTrames['accelero'] = self.nbTrames['accelero'] + len(tramesAcc)
        if self.e_magneto:
            startMagneto = startEpochMs + self.nbTrames['magneto'] * self.recordingPeriod['magneto']
            self.amgToCsv(tramesMag, prefix, 'magneto', startMagneto, self.recordingPeriod['magneto'], firstPass)
            self.nbTrames['magneto'] = self.nbTrames['magneto'] + len(tramesMag)
        if self.e_battery:
            startBattery = startEpochMs + self.nbTrames['battery'] * self.recordingPeriod['battery']
            self.batToCsv(tramesBat, prefix, 'battery', startBattery, self.recordingPeriod['battery'], firstPass)
            self.nbTrames['battery'] = self.nbTrames['battery'] + len(tramesBat)

        #self.nbTrames['gyro'] = self.nbTrames['gyro'] + len(tramesGyr)

        #self.infoToCsv(tramesInf, prefix, 'info', firstPass)
        self.infoToCsv2(prefix, 'info', firstPass)
        self.nbTrames['info'] = self.nbTrames['info'] + len(tramesInf)


    def logProcessInfo(self):
        suffix = ['gps', 'accelero', 'magneto', 'gyro', 'info', 'battery']
        for s in suffix:
            self.log('nb de trames ' + s + ' : ' + str(self.nbTrames[s]),1)

