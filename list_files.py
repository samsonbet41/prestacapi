import os

def list_files_recursive(directory, output_file, indent_level=0, root_dir=None):
    # Définir root_dir lors du premier appel
    if root_dir is None:
        root_dir = directory
    
    try:
        # Liste des extensions à inclure
        valid_extensions = ('.php', '.css', '.js')
        
        # Ignorer le dossier 'vendor' s'il est à la racine
        if os.path.abspath(directory) == os.path.join(os.path.abspath(root_dir), 'vendor'):
            return
        
        # Parcourir tous les éléments du répertoire
        for item in sorted(os.listdir(directory)):
            item_path = os.path.join(directory, item)
            
            # Vérifier si c'est un fichier
            if os.path.isfile(item_path):
                # Inclure les fichiers avec les extensions spécifiées ou commençant par un point
                if item.startswith('.') or item.endswith(valid_extensions):
                    output_file.write('  ' * indent_level + f'├── {item}\n')
            
            # Vérifier si c'est un dossier
            elif os.path.isdir(item_path):
                output_file.write('  ' * indent_level + f'├── {item}/\n')
                # Appel récursif pour les sous-dossiers
                list_files_recursive(item_path, output_file, indent_level + 1, root_dir)
    
    except PermissionError:
        output_file.write('  ' * indent_level + f'├── [Permission denied] {item}\n')
    except Exception as e:
        output_file.write('  ' * indent_level + f'├── [Error] {item}: {str(e)}\n')

def main():
    # Obtenir le répertoire courant
    current_dir = os.getcwd()
    
    # Nom du fichier de sortie
    output_filename = 'site_structure.txt'
    
    # Créer/ouvrir le fichier .txt
    with open(output_filename, 'w', encoding='utf-8') as output_file:
        output_file.write(f'Structure du site : {current_dir}\n')
        output_file.write('└──\n')
        list_files_recursive(current_dir, output_file, root_dir=current_dir)
    
    print(f"Le fichier '{output_filename}' a été créé avec la structure du site.")

if __name__ == "__main__":
    main()