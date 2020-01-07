# Deckle : automatisation du montage d'environnements de développement virtualisés

## Concept

Parce que Docker ne fonctionne (correctement) que sous Linux, et que nous sommes nombreux à travailler sous Mac,
nous avons choisi de faire tourner nos environnements de référence dans une machine virtuelle Ubuntu.

L'administration d'un tel environnement peut s'avérer complexe, voire déroutant pour un junior. Deckle a vocation
à simplifier cette administration en automatisant de nombreuses tâches, aussi bien en rapport avec le 
 déploiement de l'environnement qu'avec son utilisation quotidienne.  
 
Et parce que son environnement est riche, on a fait en sorte que sous Linux aussi, Deckle devienne notre environnement de développement de référence.


## Composants

Pour fonctionner de manière optimale, l'ecosystème Deckle requiert (notamment) les compsoants suivants :

 - macOs
   - [Virtual Box](http://www.virtualbox.org)
   - [Homebrew](http://www.homebrew.com)
   - [Mutagen](http://www.mutagen.io)
   - [Vagrant](http://www.vagrantup.com)     

 - Linux (Ubuntu/Debian)
   - [Virtual Box](http://www.virtualbox.org)
   - [Mutagen](http://www.mutagen.io)
   - [Vagrant](http://www.vagrantup.com)     



## Paramétrage

### Installation des dépendances

La majorité des outils en ligne de commande nécessaires au bon fonctionnement de Deckle sont soit natifs 
sur macOs soit installables avec `brew`. L'installateur de Deckle procédera à l'installation de ces dépendances.  

#### Virtual Box

Téléchargez l'installateur sur le [site officiel](https://www.virtualbox.org/wiki/Downloads).

#### Vagrant

- macOs
 - Télécharger l'installateur sur le [site officiel](http://www.vagrantup.com) 
- GNU/Linux (Ubuntu/Debian)
 - l'installateur de Deckle procédera à l'installation pour vous

#### Mutagen

Pour assurer des performances maximales, nous n'utilisons pas de montage de volume entre l'hôte et le container, mais un 
outil de synchronisation ultra performant qui installe un daemon dans le container et assure en permanence une synchronisation
bi-directionelle entre l'hôte et le container, avec une precedence donnée à l'hôte.

Cet outil s'appelle `mutagen` et s'installe sous macOs à l'aide de `brew`.   

NOTE : Si vous tentez d'utilisez Deckle sur une autre plateforme que macOs (Linux notamment), il faut télécharger le package sur [la page GitHub](https://github.com/mutagen-io/mutagen/releases) du projet.

### Installation de Deckle

Dans un premier temps, rendez-vous sur [deckle.adimeo.eu](https://deckle.adimeo.eu) pour télécharger la dernière version de Deckle.

Ensuite, pour installer l'environnement nécessaire au fonctionnement de Deckle, exécutez :

```shell script
deckle install
```

Dans le cas où tout s'est bien passé, vous devriez pouvoir accéder à [la machine virtuelle Deckle](http://portainer.deckle.local) et voir les containers en fonctionnement sur Portainer (identifiants par défaut : admin/portainer).


### Fine tuning

#### Authentification SSH

Le plus confortable pour se connecter en SSH sera le recours à une clé SSH, et une configuration de votre client.

Le préalable est donc de disposer d'une paire de clés RSA associée à son compte utilisateur. Idéalement, cette même 
clé devrait être associée à votre compte GitHub.

La première étape consiste donc à copier votre clé publique sur la VM :

```shell script
deckle vm:ssh:copy-id
```

Ensuite, il faut indiquer à SSH que l'on souhaite propager sa clé privée dans les sessions distantes (ce qui permet de ne PAS 
la copier physiquement dans la VM) en éditant votre fichier de config SSH :


```shell script
vi ~/.ssh/config
```

Et en y ajoutant la section suivante :

```apacheconfig
Host deckle-vm
        Hostname deckle-vm
        User deckle
        AddKeysToAgent yes
        ServerAliveInterval 60
        ForwardAgent Yes
        IdentitiesOnly yes
        UseKeychain yes # macOs uniquement
```

## Travailler avec Deckle

### Nouveau projet

Pour déployer un nouveau projet dans la machine virtuelle Deckle, il vous suffit de vous placer dans le dossier racine de votre projet et d'exécuter :

```shell script
deckle bootstrap <projectid> <provider/template>
```

Où :

 - <projectid> est l'identifiant de votre projet. Attention, celui-ci doit être unique et ne comporter aucun caractère spécial (lettres seulement)
 - <provider/template> est le nom du template de configuration Deckle à utiliser. La liste des templates peut être obtenue en exécutant :
 
 ```shell script
 deckle templates:list
 ```
ou en consultant le [dépôt officiel](https://github.com/adimeo-lab/deckle-templates).

Une fois le projet bootstrapé, il sera éventuellement nécessaire d'adapter le template au projet en cours en parcourant les fichiers du dossier ```./deckle``` nouvellement créé. Chaque template fourni un README expliquant ses grands principes de fonctionnement et ce qu'il convient d'ajuster ou non.

### Initialisation de l'environnement

Pour déployer un projet préalablement bootstrapé (i.e. disposant d'une configuration opérationnelle dans un dossir ```./deckle```), il suffit d'exécuter :

```shell script
cd project/diretory
deckle up
```

Si la configuration Deckle du projet a été mise à jour, il peut être nécessaire de redéployer certains fichiers locaux en réexécutant :

```shell script
cd project/diretory
deckl init
```

### Synchronisation des fichiers sources dans l'image

Chaque projet doit fournir un fichier `deckle/mutagen.yml` pour fonctionner correctement avec Deckle. Pour démarrer la
synchronisation, exécuter :

````shell script
cd project/directory
deckle mutagen:sync start
````

NOTE : la commande ```deckle mutagen:sync <operation>``` peut être abrégée en ```deckle sync <operation>```

Pour contrôler le bon fonctionnement de la synchronisation en continu :

```shell script
deckle mutagen:monitor
```

Ou pour surveiller une première synchronisation :

```shell script
deckle mutagen:monitor -u
```

Auquel cas le moniteur s'interrompera automatiquement lorsque toutes les sessions seront synchronisées.

### Installation des dépendances

Pour installer les dépendances d'un projet à l'aide de `composer`, exécutez :

```shell script
deckle composer install
```

NOTE: si vous voulez passer des paramètres à `composer`, il faut les faire précéder de la séquence `--` afin
que Deckle ne les considère pas comme lui étant destinés. Exemple :

```shell script
deckle composer install -- --no-interaction
```

### Ouvrir un shell dans un container

Pour accéder rapidement aux images de l'environnement en shell, exécutez :

```shell script
deckle sh
```

Ceci ouvrira une session shell automatiquement dans le container identifié dans la section `app.container` de la configuration. Si vous souhaitez accéder à un autre container de la machine virtuelle, utilisez :

```shell script
deckle sh <container-name> 
# par exemple
deckle sh mysql57
```

### Ouvrir un shell dans la Deckle Machine

Pour accéder en ssh à la machine Deckle, utilisez simplement :

```shell script
deckle vm:ssh
```

Cette commande ouvrira un shell ssh en tant que l'utilisateur `deckle`. Pour passer en root, exécutez `sudo -s` une fois dans le shell distant. Au besoin, le mot de passe par défaut de l'utilisateur `deckle` est ... `deckle`.

### Aide et autres commandes

Pour obtenir plus d'informations sur ces commandes et/ou découvrir les autres commandes disponibles, consultez l'aide embarquée :

```shell script
# afficher toutes les commandes disponibles 
deckle list

# afficher le détail de l'utilisation d'une commande
deckle help <command>
# exemple
deckle help vm:ssh
```

# Autres services

La Deckle Machine intègre un certain nombre de services, dont la plupart offrent une interface web d'accès et/ou de monitoring. Les principales sont listées dans ce chapitre. Pour voir la liste de toutes les applis installées, exécutez :

```shell script
deckle apps list
```

Et pour savoir si elles exposent une interface web (ainsi que leur état), utilisez :

```shell script
deckle apps status
```

Si une app expose un port 80, la convention veut que cette interface web soit accessible par l'url `http://<app-name>.deckle.local`.

## Portainer

http://portainer.deckle.local
admin/portainer

## Traefik

Traefik est le reverse proxy qui permet d'accèder aux différents container via les adresses en `*.deckle.local`. Vous pouvez accéder à son UI à l'adresse ci-dessous pour consulter la liste des routers HTTP ou TCP qui sont configurés dans l'environnement Deckle :

http://traefik.deckle.local


## phpMyAdmin (pour MySQL 5.7)


http://pma57.deckle.local

## Maildev

Maildev est un serveur de mail local permettant d'intercepter tout mail sortant d'une application et de les consulter dans un webmail accessible sur :

http://maildev.deckle.local

Pour que vos applications utilisent ce service en développement, il suffit de les configurer pour utiliser l'hôte `maildev` en guise de serveur SMTP.

## XHGui

Cette application permet de consulter des traces de profiling générées par XHProf. Pour les projets utilisant un template Deckle officiel (provider "adimeo"), il suffit d'ajouter `?xhprofile` ou `&xhprofile` dans n'importe quelle URL du projet. Ce paramètre peut également être passé en POST. 

Sa présence déclenchera la collecte d'informations de profiling que vous retrouverez sur :

http://xhgui.deckle.local


# Etendre Deckle

## Commandes locales

Si vous souhaitez écrire une commande spécifique à un projet, vous pouvez créer une classe dans `./deckle/commands`.

Le fichier contenant la classe doit s'appeler `*Command.php` et étendre `AdimeoLab\Deckle\AbstractDeckleCommand`.


| Attention : pour développer votre commande, il vous faudra installer les sources de Deckle dans votre dossier `./deckle`,
| mais ces sources ne seront pas celles utilisées à l'exécution. A la place, ce sont celles embarquées dans le binaire 
| `deckle(.phar)` qui le seront. Il n'y a pour l'heure aucun mécanisme de contrôle de versions pour les commandes locales.


# Support

## Problèmes connus

### Certaines commandes impliquant une connexion SSH depuis la VM échouent avec le message 'Host key verification failed.
'

Le message `Host key verification failed.` signifie en général que la machine distante n'est pas connue de la VM (i.e. 
que son identité est absente du fichier `~/.ssh/known_hosts`). 

Pour remédier à ce problème, il faut faire une première connexion depuis la VM elle-même sur la machine concernée.

### La résolution des hôtes *.deckle.local ne fonctionne pas sur macOs

Si ce problème survient, tentez de redémarrer le sous-système DNS de macOs comme suit : 

```shell script
dscacheutil -flushcache
sudo killall -HUP mDNSResponder
``` 

En cas de problème avec la mise en oeuvre de DNSmasq, consulter [ce blog](https://www.stevenrombauts.be/2018/01/use-dnsmasq-instead-of-etc-hosts/#configure-as-default-dns-resolver-in-macos)

### Toutes les URL en `http://*.deckle.local` affichent un simple message `404 not found`

Ceci est du à un problème de configuration de Traefik. Pour consulter l'état de Traefik, tentez d'accéder à son UI par l'adresse suivante :

```
http://traefik.deckle.local:8080
```

## Autres problèmes

Si vous rencontrez des problèmes durant l'installation ou durant l'utilisation de Deckle, merci de contacter [Gauthier](gdelamarre@gmail.com) ou de créer une issue sur le [dépôt GiHub](https://github.com/adimeo-lab/deckle/issues).
